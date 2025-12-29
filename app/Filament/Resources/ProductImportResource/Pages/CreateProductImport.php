<?php

namespace App\Filament\Resources\ProductImportResource\Pages;

use App\Filament\Resources\ProductImportResource;
use App\Imports\ProductImport;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CreateProductImport extends CreateRecord
{
    protected static string $resource = ProductImportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['file_name'] = basename($data['file_path'] ?? '');
        $data['file_type'] = pathinfo($data['file_name'] ?? '', PATHINFO_EXTENSION) ?: null;
        $data['file_size'] = isset($data['file_path']) && Storage::disk('local')->exists($data['file_path'])
            ? Storage::disk('local')->size($data['file_path'])
            : null;

        $data['total_rows'] = $this->countTotalRows($data['file_path'] ?? null);
        $data['processed_rows'] = 0;
        $data['successful_rows'] = 0;
        $data['failed_rows'] = 0;
        $data['skipped_rows'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $import = $this->record;

        Excel::queueImport(
            new ProductImport($import, $import->field_mapping ?? [], $import->options ?? []),
            $import->file_path,
            'local'
        );
    }

    private function countTotalRows(?string $filePath): int
    {
        if (! $filePath) {
            return 0;
        }

        try {
            $absolute = Storage::disk('local')->path($filePath);
            $data = Excel::toArray([], $absolute);
            $rows = count($data[0] ?? []);

            return max(0, $rows - 1); // subtract header
        } catch (\Throwable) {
            return 0;
        }
    }
}

