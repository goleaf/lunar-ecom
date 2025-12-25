<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\DigitalProductService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Controller for handling digital product downloads.
 */
class DownloadController extends Controller
{
    public function __construct(
        protected DigitalProductService $digitalProductService
    ) {}

    /**
     * List customer's downloads.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $customer = auth()->user()?->customer;

        if (!$customer) {
            abort(403, 'You must be logged in to view downloads.');
        }

        $downloads = \App\Models\Download::where('customer_id', $customer->id)
            ->with(['digitalProduct.product', 'order'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('storefront.downloads.index', [
            'downloads' => $downloads,
        ]);
    }

    /**
     * Download a file using token.
     *
     * @param  Request  $request
     * @param  string  $token
     * @return Response|\Illuminate\Http\RedirectResponse
     */
    public function download(Request $request, string $token)
    {
        // Validate download
        $download = $this->digitalProductService->validateDownload($token);

        if (!$download) {
            abort(404, 'Download link is invalid, expired, or limit reached.');
        }

        // Check if customer can download (for refund policy)
        if (!$this->digitalProductService->canDownload($download)) {
            abort(403, 'This download is no longer available.');
        }

        // Get version if specified
        $version = $request->query('version');

        // Get file response
        $response = $this->digitalProductService->getFileResponse($download, $version);

        if (!$response) {
            abort(404, 'File not found.');
        }

        return $response;
    }

    /**
     * Get download information (for AJAX requests).
     *
     * @param  Request  $request
     * @param  string  $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function info(Request $request, string $token)
    {
        $download = \App\Models\Download::where('download_token', $token)->first();

        if (!$download) {
            return response()->json(['error' => 'Download not found'], 404);
        }

        $digitalProduct = $download->digitalProduct;
        $product = $digitalProduct?->product;

        return response()->json([
            'download' => [
                'token' => $download->download_token,
                'downloads_count' => $download->downloads_count,
                'download_limit' => $digitalProduct->download_limit,
                'expires_at' => $download->expires_at?->toIso8601String(),
                'is_expired' => $download->isExpired(),
                'is_limit_reached' => $download->isLimitReached(),
                'license_key' => $download->license_key,
            ],
            'product' => [
                'id' => $product->id,
                'name' => $product->translateAttribute('name'),
                'file_size' => $digitalProduct->getFormattedFileSize(),
                'version' => $digitalProduct->version,
            ],
            'versions' => $digitalProduct->versions()
                ->where('is_current', false)
                ->orderBy('released_at', 'desc')
                ->get()
                ->map(function ($version) {
                    return [
                        'version' => $version->version,
                        'release_notes' => $version->release_notes,
                        'released_at' => $version->released_at?->toIso8601String(),
                    ];
                }),
        ]);
    }

    /**
     * Resend download email.
     *
     * @param  Request  $request
     * @param  \App\Models\Download  $download
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendEmail(Request $request, int $downloadId)
    {
        $download = \App\Models\Download::findOrFail($downloadId);
        $customer = auth()->user()?->customer;

        if (!$customer || $download->customer_id !== $customer->id) {
            abort(403, 'Unauthorized');
        }

        // Dispatch job to send email
        \App\Jobs\SendDigitalProductDownloadEmail::dispatch($download);

        return response()->json([
            'success' => true,
            'message' => 'Download instructions email has been sent.',
        ]);
    }
}
