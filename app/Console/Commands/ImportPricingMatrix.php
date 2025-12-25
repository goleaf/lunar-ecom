<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\PriceMatrix;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImportPricingMatrix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pricing:import 
                            {file : Path to CSV/JSON file}
                            {--format=csv : File format (csv or json)}
                            {--dry-run : Run without saving changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import pricing matrices from CSV or JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');
        $format = $this->option('format');
        $dryRun = $this->option('dry-run');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info("Importing pricing matrices from: {$file}");

        try {
            if ($format === 'json') {
                $data = $this->importFromJson($file);
            } else {
                $data = $this->importFromCsv($file);
            }

            $this->info("Found " . count($data) . " pricing matrices to import");

            $bar = $this->output->createProgressBar(count($data));
            $bar->start();

            $imported = 0;
            $errors = 0;

            DB::beginTransaction();

            foreach ($data as $row) {
                try {
                    $validated = $this->validateRow($row);
                    
                    if (!$validated) {
                        $errors++;
                        $bar->advance();
                        continue;
                    }

                    if (!$dryRun) {
                        $this->createPriceMatrix($row);
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->warn("Error importing row: " . $e->getMessage());
                    $errors++;
                }

                $bar->advance();
            }

            if ($dryRun) {
                DB::rollBack();
                $this->newLine();
                $this->info("DRY RUN: Would import {$imported} matrices, {$errors} errors");
            } else {
                DB::commit();
                $this->newLine();
                $this->info("Successfully imported {$imported} pricing matrices");
                if ($errors > 0) {
                    $this->warn("{$errors} rows had errors");
                }
            }

            $bar->finish();
            $this->newLine();

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Import failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Import from JSON file.
     */
    protected function importFromJson(string $file): array
    {
        $content = file_get_contents($file);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON: " . json_last_error_msg());
        }

        return is_array($data) ? $data : [$data];
    }

    /**
     * Import from CSV file.
     */
    protected function importFromCsv(string $file): array
    {
        $data = [];
        $handle = fopen($file, 'r');

        if ($handle === false) {
            throw new \Exception("Could not open file: {$file}");
        }

        // Read header
        $headers = fgetcsv($handle);
        
        if (!$headers) {
            throw new \Exception("Could not read CSV headers");
        }

        // Read rows
        while (($row = fgetcsv($handle)) !== false) {
            $data[] = array_combine($headers, $row);
        }

        fclose($handle);

        return $data;
    }

    /**
     * Validate a row of data.
     */
    protected function validateRow(array $row): bool
    {
        $validator = Validator::make($row, [
            'product_id' => 'required|exists:lunar_products,id',
            'matrix_type' => 'required|in:quantity,customer_group,region,mixed',
            'rules' => 'required',
            'is_active' => 'sometimes|boolean',
            'priority' => 'sometimes|integer',
            'starts_at' => 'sometimes|date',
            'ends_at' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            $this->newLine();
            $this->warn("Validation errors: " . $validator->errors()->first());
            return false;
        }

        // Validate rules JSON
        if (is_string($row['rules'])) {
            $rules = json_decode($row['rules'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->newLine();
                $this->warn("Invalid rules JSON: " . json_last_error_msg());
                return false;
            }
        }

        return true;
    }

    /**
     * Create price matrix from row data.
     */
    protected function createPriceMatrix(array $row): PriceMatrix
    {
        $rules = is_string($row['rules']) ? json_decode($row['rules'], true) : $row['rules'];

        return PriceMatrix::create([
            'product_id' => $row['product_id'],
            'matrix_type' => $row['matrix_type'],
            'rules' => $rules,
            'is_active' => $row['is_active'] ?? true,
            'priority' => $row['priority'] ?? 0,
            'starts_at' => $row['starts_at'] ?? null,
            'ends_at' => $row['ends_at'] ?? null,
            'description' => $row['description'] ?? null,
        ]);
    }
}
