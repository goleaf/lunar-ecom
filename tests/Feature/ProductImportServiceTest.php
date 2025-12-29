<?php

namespace Tests\Feature;

use App\Models\ProductImport;
use App\Models\ProductImportRow;
use App\Services\ProductImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_report_writes_import_report_with_failed_rows(): void
    {
        $this->seed();

        $import = ProductImport::factory()->create([
            'total_rows' => 2,
            'processed_rows' => 2,
            'successful_rows' => 1,
            'failed_rows' => 1,
            'skipped_rows' => 0,
        ]);

        ProductImportRow::factory()->create([
            'product_import_id' => $import->id,
            'row_number' => 2,
            'status' => 'success',
            'sku' => 'SKU-OK',
        ]);

        ProductImportRow::factory()->create([
            'product_import_id' => $import->id,
            'row_number' => 3,
            'status' => 'failed',
            'sku' => 'SKU-BAD',
            'validation_errors' => ['sku' => 'SKU already exists'],
            'error_message' => 'Validation failed',
        ]);

        app(ProductImportService::class)->generateReport($import);

        $import->refresh();

        $this->assertIsArray($import->import_report);
        $this->assertSame(2, $import->import_report['total_rows']);
        $this->assertSame(1, $import->import_report['failed_rows']);
        $this->assertCount(1, $import->import_report['errors']);
        $this->assertSame(3, $import->import_report['errors'][0]['row_number']);
        $this->assertSame('SKU-BAD', $import->import_report['errors'][0]['sku']);
    }
}

