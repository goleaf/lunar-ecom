<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ProductImportResource;
use App\Filament\Resources\ProductImportResource\Pages\CreateProductImport;
use App\Filament\Resources\ProductImportResource\Pages\ListProductImports;
use App\Models\Product;
use App\Models\ProductImport;
use App\Models\ProductImportRollback;
use App\Models\ProductImportRow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class FilamentProductImportResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_import_index_create_and_view_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $import = ProductImport::factory()->create();

        $slug = ProductImportResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.view", ['record' => $import->getKey()]))->assertOk();
    }

    public function test_product_import_can_be_created_via_filament_create_page_and_queues_excel_import(): void
    {
        $this->seed();

        Storage::fake('local');
        Excel::fake();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        Livewire::test(CreateProductImport::class)
            ->set('data.file_path', UploadedFile::fake()->createWithContent('products.csv', "sku,name,price\nSKU-1,Test,9.99\n"))
            ->set('data.options.action', 'create_or_update')
            ->set('data.options.skip_errors', true)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount((new ProductImport())->getTable(), 1);

        $import = ProductImport::query()->firstOrFail();
        $this->assertNotEmpty($import->file_path);

        Excel::assertQueued(
            $import->file_path,
            'local',
            fn ($queuedImport) => $queuedImport instanceof \App\Imports\ProductImport
        );
    }

    public function test_product_import_can_be_rolled_back_via_table_action(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();

        $import = ProductImport::factory()->completed()->create([
            'successful_rows' => 1,
            'processed_rows' => 1,
            'total_rows' => 1,
        ]);

        ProductImportRow::factory()
            ->successForProduct($product, ['action' => 'created'])
            ->create([
                'product_import_id' => $import->id,
                'row_number' => 2,
            ]);

        Livewire::test(ListProductImports::class)
            ->callTableAction('rollback', $import);

        $this->assertNull(Product::find($product->id));

        $this->assertDatabaseHas((new ProductImportRollback())->getTable(), [
            'product_import_id' => $import->id,
            'product_id' => $product->id,
        ]);
    }
}

