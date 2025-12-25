<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PriceMatrix;
use App\Models\Product;
use Illuminate\Support\Facades\File;

class ExportPricingMatrix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pricing:export 
                            {--format=csv : Export format (csv or json)}
                            {--product= : Export for specific product ID}
                            {--type= : Export specific matrix type}
                            {--output= : Output file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export pricing matrices to CSV or JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $format = $this->option('format');
        $productId = $this->option('product');
        $type = $this->option('type');
        $output = $this->option('output') ?? $this->getDefaultOutputPath($format);

        $query = PriceMatrix::with('product');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($type) {
            $query->where('matrix_type', $type);
        }

        $matrices = $query->get();

        if ($matrices->isEmpty()) {
            $this->warn("No pricing matrices found to export");
            return 1;
        }

        $this->info("Exporting " . $matrices->count() . " pricing matrices to: {$output}");

        try {
            if ($format === 'json') {
                $this->exportToJson($matrices, $output);
            } else {
                $this->exportToCsv($matrices, $output);
            }

            $this->info("Successfully exported to: {$output}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Export failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Export to JSON.
     */
    protected function exportToJson($matrices, string $output): void
    {
        $data = $matrices->map(function ($matrix) {
            return [
                'id' => $matrix->id,
                'product_id' => $matrix->product_id,
                'product_sku' => $matrix->product->sku ?? null,
                'matrix_type' => $matrix->matrix_type,
                'rules' => $matrix->rules,
                'is_active' => $matrix->is_active,
                'priority' => $matrix->priority,
                'starts_at' => $matrix->starts_at?->toIso8601String(),
                'ends_at' => $matrix->ends_at?->toIso8601String(),
                'description' => $matrix->description,
                'created_at' => $matrix->created_at->toIso8601String(),
                'updated_at' => $matrix->updated_at->toIso8601String(),
            ];
        })->toArray();

        File::put($output, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Export to CSV.
     */
    protected function exportToCsv($matrices, string $output): void
    {
        $handle = fopen($output, 'w');

        if ($handle === false) {
            throw new \Exception("Could not create file: {$output}");
        }

        // Write header
        fputcsv($handle, [
            'id',
            'product_id',
            'product_sku',
            'matrix_type',
            'rules',
            'is_active',
            'priority',
            'starts_at',
            'ends_at',
            'description',
            'created_at',
            'updated_at',
        ]);

        // Write rows
        foreach ($matrices as $matrix) {
            fputcsv($handle, [
                $matrix->id,
                $matrix->product_id,
                $matrix->product->sku ?? '',
                $matrix->matrix_type,
                json_encode($matrix->rules),
                $matrix->is_active ? '1' : '0',
                $matrix->priority,
                $matrix->starts_at?->toIso8601String(),
                $matrix->ends_at?->toIso8601String(),
                $matrix->description ?? '',
                $matrix->created_at->toIso8601String(),
                $matrix->updated_at->toIso8601String(),
            ]);
        }

        fclose($handle);
    }

    /**
     * Get default output path.
     */
    protected function getDefaultOutputPath(string $format): string
    {
        $extension = $format === 'json' ? 'json' : 'csv';
        $timestamp = now()->format('Y-m-d_His');
        return storage_path("app/pricing_export_{$timestamp}.{$extension}");
    }
}
