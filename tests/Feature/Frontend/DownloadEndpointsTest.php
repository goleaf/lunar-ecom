<?php

namespace Tests\Feature\Frontend;

use App\Jobs\SendDigitalProductDownloadEmail;
use App\Lunar\Customers\CustomerHelper;
use App\Models\DigitalProduct;
use App\Models\DigitalProductVersion;
use App\Models\Download;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Lunar\Models\Order;
use Tests\TestCase;

class DownloadEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_info_returns_404_when_token_is_unknown(): void
    {
        $this->getJson(route('frontend.downloads.info', ['token' => 'does-not-exist']))
            ->assertStatus(404)
            ->assertJson([
                'error' => 'Download not found',
            ]);
    }

    public function test_download_info_returns_expected_payload_for_valid_token(): void
    {
        $product = Product::factory()->published()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->getKey(),
        ]);

        $digitalProduct = DigitalProduct::create([
            'product_variant_id' => $variant->getKey(),
            'storage_disk' => 'private',
            'file_size' => 1024,
            'file_name' => 'test.zip',
            'file_type' => 'application/zip',
            'download_limit' => 5,
        ]);

        DigitalProductVersion::create([
            'digital_product_id' => $digitalProduct->getKey(),
            'version' => '1.0.0',
            'file_path' => encrypt('digital-products/test.zip'),
            'file_size' => 1024,
            'mime_type' => 'application/zip',
            'original_filename' => 'test.zip',
            'is_current' => true,
        ]);

        $user = User::factory()->create();
        $customer = CustomerHelper::getOrCreateCustomerForUser($user);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'customer_id' => $customer->getKey(),
        ]);

        $download = Download::create([
            'customer_id' => $customer->getKey(),
            'order_id' => $order->getKey(),
            'digital_product_id' => $digitalProduct->getKey(),
            'download_token' => 'test-download-token',
            'downloads_count' => 0,
            'expires_at' => now()->addDay(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'license_key' => null,
            'license_key_sent' => false,
        ]);

        $this->getJson(route('frontend.downloads.info', ['token' => $download->download_token]))
            ->assertOk()
            ->assertJsonStructure([
                'download' => [
                    'token',
                    'downloads_count',
                    'download_limit',
                    'expires_at',
                    'is_expired',
                    'is_limit_reached',
                    'license_key',
                ],
                'product' => [
                    'id',
                    'name',
                    'file_size',
                    'version',
                ],
                'versions',
            ])
            ->assertJsonPath('download.token', $download->download_token)
            ->assertJsonPath('product.id', $product->getKey())
            ->assertJsonPath('product.version', '1.0.0');
    }

    public function test_download_resend_email_requires_authentication(): void
    {
        $product = Product::factory()->published()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->getKey(),
        ]);

        $digitalProduct = DigitalProduct::create([
            'product_variant_id' => $variant->getKey(),
            'storage_disk' => 'private',
        ]);

        $order = Order::factory()->create();

        $download = Download::create([
            'customer_id' => $order->customer_id,
            'order_id' => $order->getKey(),
            'digital_product_id' => $digitalProduct->getKey(),
            'download_token' => 'token-resend',
            'downloads_count' => 0,
        ]);

        $this->postJson(route('frontend.downloads.resend-email', ['download' => $download->getKey()]))
            ->assertUnauthorized();
    }

    public function test_download_resend_email_returns_403_for_non_owner(): void
    {
        Bus::fake();

        $product = Product::factory()->published()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->getKey(),
        ]);

        $digitalProduct = DigitalProduct::create([
            'product_variant_id' => $variant->getKey(),
            'storage_disk' => 'private',
        ]);

        $owner = User::factory()->create();
        $ownerCustomer = CustomerHelper::getOrCreateCustomerForUser($owner);
        $order = Order::factory()->create([
            'user_id' => $owner->id,
            'customer_id' => $ownerCustomer->getKey(),
        ]);

        $download = Download::create([
            'customer_id' => $ownerCustomer->getKey(),
            'order_id' => $order->getKey(),
            'digital_product_id' => $digitalProduct->getKey(),
            'download_token' => 'token-owner',
            'downloads_count' => 0,
        ]);

        $intruder = User::factory()->create();
        CustomerHelper::getOrCreateCustomerForUser($intruder);

        $this->actingAs($intruder)
            ->postJson(route('frontend.downloads.resend-email', ['download' => $download->getKey()]))
            ->assertForbidden();

        Bus::assertNotDispatched(SendDigitalProductDownloadEmail::class);
    }

    public function test_download_resend_email_dispatches_job_for_owner(): void
    {
        Bus::fake();

        $product = Product::factory()->published()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->getKey(),
        ]);

        $digitalProduct = DigitalProduct::create([
            'product_variant_id' => $variant->getKey(),
            'storage_disk' => 'private',
        ]);

        $owner = User::factory()->create();
        $ownerCustomer = CustomerHelper::getOrCreateCustomerForUser($owner);
        $order = Order::factory()->create([
            'user_id' => $owner->id,
            'customer_id' => $ownerCustomer->getKey(),
        ]);

        $download = Download::create([
            'customer_id' => $ownerCustomer->getKey(),
            'order_id' => $order->getKey(),
            'digital_product_id' => $digitalProduct->getKey(),
            'download_token' => 'token-owner-2',
            'downloads_count' => 0,
        ]);

        $this->actingAs($owner)
            ->postJson(route('frontend.downloads.resend-email', ['download' => $download->getKey()]))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        Bus::assertDispatched(SendDigitalProductDownloadEmail::class, function (SendDigitalProductDownloadEmail $job) use ($download) {
            return $job->download->getKey() === $download->getKey();
        });
    }
}

