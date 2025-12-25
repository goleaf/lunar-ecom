<?php

namespace App\Services;

use App\Models\DigitalProduct;
use App\Models\DigitalProductVersion;
use App\Models\Download;
use App\Models\DownloadLog;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;
use Lunar\Models\ProductVariant;

/**
 * Service for managing digital products and downloads.
 */
class DigitalProductService
{
    /**
     * Generate a download link for a customer/order.
     *
     * @param  Order  $order
     * @param  DigitalProduct  $digitalProduct
     * @param  int|null  $customerId
     * @return Download
     */
    public function generateDownloadLink(Order $order, DigitalProduct $digitalProduct, ?int $customerId = null): Download
    {
        return DB::transaction(function () use ($order, $digitalProduct, $customerId) {
            // Check if download already exists for this order/product combination
            $existingDownload = Download::where('order_id', $order->id)
                ->where('digital_product_id', $digitalProduct->id)
                ->first();

            if ($existingDownload) {
                return $existingDownload;
            }

            // Calculate expiry date
            $expiresAt = null;
            if ($digitalProduct->download_expiry_days) {
                $expiresAt = now()->addDays($digitalProduct->download_expiry_days);
            }

            // Generate license key if required
            $licenseKey = null;
            if ($digitalProduct->requiresLicense()) {
                $licenseKey = $this->generateLicenseKey($digitalProduct->license_key_pattern);
            }

            // Create download record
            $download = Download::create([
                'customer_id' => $customerId ?? $order->customer_id,
                'order_id' => $order->id,
                'digital_product_id' => $digitalProduct->id,
                'download_token' => Str::random(64),
                'downloads_count' => 0,
                'expires_at' => $expiresAt,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'license_key' => $licenseKey,
                'license_key_sent' => false,
            ]);

            return $download;
        });
    }

    /**
     * Track a download event.
     *
     * @param  Download  $download
     * @param  string|null  $version
     * @param  int|null  $bytesDownloaded
     * @param  bool  $completed
     * @return DownloadLog
     */
    public function trackDownload(
        Download $download,
        ?string $version = null,
        ?int $bytesDownloaded = null,
        bool $completed = true
    ): DownloadLog {
        $log = DownloadLog::create([
            'download_id' => $download->id,
            'digital_product_id' => $download->digital_product_id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'version' => $version ?? $download->digitalProduct->version,
            'bytes_downloaded' => $bytesDownloaded,
            'completed' => $completed,
            'downloaded_at' => now(),
        ]);

        // Increment download count
        $download->incrementDownloadCount();

        return $log;
    }

    /**
     * Validate a download token.
     *
     * @param  string  $token
     * @return Download|null
     */
    public function validateDownload(string $token): ?Download
    {
        $download = Download::where('download_token', $token)->first();

        if (!$download) {
            return null;
        }

        // Check if expired
        if ($download->isExpired()) {
            return null;
        }

        // Check if limit reached
        if ($download->isLimitReached()) {
            return null;
        }

        // Check if digital product is active
        if (!$download->digitalProduct || !$download->digitalProduct->is_active) {
            return null;
        }

        // Check if file exists
        if (!$download->digitalProduct->fileExists()) {
            return null;
        }

        return $download;
    }

    /**
     * Upload and store a digital product file.
     *
     * @param  DigitalProduct  $digitalProduct
     * @param  UploadedFile  $file
     * @return bool
     */
    public function uploadFile(DigitalProduct $digitalProduct, UploadedFile $file): bool
    {
        try {
            // Generate unique filename
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = 'digital-products/' . $digitalProduct->product_id . '/' . $filename;

            // Store file in private storage
            $storedPath = Storage::disk('private')->putFileAs(
                'digital-products/' . $digitalProduct->product_id,
                $file,
                $filename
            );

            // Encrypt the file path
            $encryptedPath = encrypt($storedPath);

            // Update digital product
            $digitalProduct->update([
                'file_path' => $encryptedPath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'original_filename' => $file->getClientOriginalName(),
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to upload digital product file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate license key based on pattern.
     *
     * @param  string  $pattern
     * @return string
     */
    public function generateLicenseKey(string $pattern): string
    {
        $key = '';
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Exclude confusing characters

        for ($i = 0; $i < strlen($pattern); $i++) {
            if ($pattern[$i] === 'X') {
                $key .= $chars[random_int(0, strlen($chars) - 1)];
            } else {
                $key .= $pattern[$i];
            }
        }

        return $key;
    }

    /**
     * Create download links for all digital products in an order.
     *
     * @param  Order  $order
     * @return array Array of created downloads
     */
    public function createDownloadsForOrder(Order $order): array
    {
        $downloads = [];

        foreach ($order->lines as $line) {
            $purchasable = $line->purchasable;

            if ($purchasable instanceof ProductVariant) {
                $product = $purchasable->product;

                if ($product->is_digital && $product->digitalProduct) {
                    $download = $this->generateDownloadLink(
                        $order,
                        $product->digitalProduct,
                        $order->customer_id
                    );

                    $downloads[] = $download;
                }
            }
        }

        return $downloads;
    }

    /**
     * Get file content for download.
     *
     * @param  Download  $download
     * @param  string|null  $version
     * @return \Illuminate\Http\Response|null
     */
    public function getFileResponse(Download $download, ?string $version = null)
    {
        $digitalProduct = $download->digitalProduct;

        if (!$digitalProduct) {
            return null;
        }

        // Get file path (use version if specified)
        $filePath = null;
        $originalFilename = null;

        if ($version) {
            $productVersion = DigitalProductVersion::where('digital_product_id', $digitalProduct->id)
                ->where('version', $version)
                ->first();

            if ($productVersion) {
                $filePath = $productVersion->getDecryptedFilePath();
                $originalFilename = $productVersion->original_filename;
            }
        }

        // Fallback to current file
        if (!$filePath) {
            $filePath = $digitalProduct->getDecryptedFilePath();
            $originalFilename = $digitalProduct->original_filename;
        }

        if (!$filePath || !Storage::disk('private')->exists($filePath)) {
            return null;
        }

        // Track download
        $this->trackDownload($download, $version);

        // Return file download response
        return Storage::disk('private')->download(
            $filePath,
            $originalFilename ?? 'download'
        );
    }

    /**
     * Create a new version of a digital product.
     *
     * @param  DigitalProduct  $digitalProduct
     * @param  UploadedFile  $file
     * @param  string  $version
     * @param  string|null  $releaseNotes
     * @param  bool  $notifyCustomers
     * @return DigitalProductVersion
     */
    public function createVersion(
        DigitalProduct $digitalProduct,
        UploadedFile $file,
        string $version,
        ?string $releaseNotes = null,
        bool $notifyCustomers = false
    ): DigitalProductVersion {
        // Mark previous current version as not current
        DigitalProductVersion::where('digital_product_id', $digitalProduct->id)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        // Generate unique filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = 'digital-products/' . $digitalProduct->product_id . '/versions/' . $version . '/' . $filename;

        // Store file in private storage
        $storedPath = Storage::disk('private')->putFileAs(
            'digital-products/' . $digitalProduct->product_id . '/versions/' . $version,
            $file,
            $filename
        );

        // Encrypt the file path
        $encryptedPath = encrypt($storedPath);

        // Create version record
        $productVersion = DigitalProductVersion::create([
            'digital_product_id' => $digitalProduct->id,
            'version' => $version,
            'file_path' => $encryptedPath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'original_filename' => $file->getClientOriginalName(),
            'release_notes' => $releaseNotes,
            'is_current' => true,
            'notify_customers' => $notifyCustomers,
            'released_at' => now(),
        ]);

        // Update main digital product version
        $digitalProduct->update([
            'version' => $version,
        ]);

        // Send notifications if requested
        if ($notifyCustomers) {
            // Dispatch job to notify customers
            \App\Jobs\NotifyDigitalProductUpdate::dispatch($digitalProduct, $productVersion);
        }

        return $productVersion;
    }

    /**
     * Check if customer can download (for refund policy).
     *
     * @param  Download  $download
     * @return bool
     */
    public function canDownload(Download $download): bool
    {
        // If already downloaded, check refund policy
        if ($download->downloads_count > 0) {
            // Digital products cannot be returned after download
            // This is handled by the order refund system
            return true; // Allow re-downloads within limits
        }

        return $download->isAvailable();
    }
}
