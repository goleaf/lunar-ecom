<?php

namespace App\Admin\Extensions\Resources;

use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\ResourceExtension;

/**
 * Extension for Product Resource to ensure name and image columns are displayed.
 */
class ProductResourceExtension extends ResourceExtension
{
    /**
     * Extend the table schema to ensure product names and images are visible.
     */
    public function extendTable(Table $table): Table
    {
        $existingColumns = $table->getColumns();
        
        // Find existing name, image, and brand columns
        $nameColumn = null;
        $imageColumn = null;
        $brandColumn = null;
        
        foreach ($existingColumns as $column) {
            $columnName = $column->getName();
            if (str_contains($columnName, 'thumbnail') || str_contains($columnName, 'image') || $column instanceof ImageColumn) {
                $imageColumn = $column;
            }
            if ($columnName === 'name' || str_contains($columnName, 'name')) {
                $nameColumn = $column;
            }
            if ($columnName === 'brand' || str_contains($columnName, 'brand')) {
                $brandColumn = $column;
            }
        }
        
        $columns = [];
        
        // Add or ensure image column is first
        if ($imageColumn) {
            // Make sure it's visible and properly configured
            $columns[] = ImageColumn::make($imageColumn->getName())
                ->label('Image')
                ->getStateUsing(function ($record) {
                    $firstMedia = $record->getFirstMedia('images');
                    return $firstMedia ? $firstMedia->getUrl('thumb') : null;
                })
                ->defaultImageUrl(config('lunar.media.fallback.url'))
                ->circular(false)
                ->size(50)
                ->visible(true);
        } else {
            $columns[] = ImageColumn::make('thumbnail')
                ->label('Image')
                ->getStateUsing(function ($record) {
                    $firstMedia = $record->getFirstMedia('images');
                    return $firstMedia ? $firstMedia->getUrl('thumb') : null;
                })
                ->defaultImageUrl(config('lunar.media.fallback.url'))
                ->circular(false)
                ->size(50);
        }
        
        // Add or ensure name column
        if ($nameColumn) {
            // Make sure it's visible and properly configured
            $columns[] = TextColumn::make($nameColumn->getName())
                ->label('Name')
                ->getStateUsing(function ($record) {
                    return $record->translateAttribute('name') ?? $record->translate('name') ?? 'N/A';
                })
                ->searchable()
                ->sortable()
                ->wrap()
                ->visible(true);
        } else {
            $columns[] = TextColumn::make('name')
                ->label('Name')
                ->getStateUsing(function ($record) {
                    return $record->translateAttribute('name') ?? $record->translate('name') ?? 'N/A';
                })
                ->searchable()
                ->sortable()
                ->wrap();
        }
        
        // Add or ensure brand column
        $brandColumnConfig = TextColumn::make('brand.name')
            ->label('Brand')
            ->getStateUsing(function ($record) {
                // Load brand relationship if not already loaded
                if (!$record->relationLoaded('brand')) {
                    $record->load('brand');
                }
                return $record->brand?->name ?? 'N/A';
            })
            ->searchable(query: function ($query, string $search) {
                return $query->whereHas('brand', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->sortable(query: function ($query, string $direction) {
                $tablePrefix = config('lunar.database.table_prefix', 'lunar_');
                return $query->join($tablePrefix . 'brands', $tablePrefix . 'products.brand_id', '=', $tablePrefix . 'brands.id')
                    ->orderBy($tablePrefix . 'brands.name', $direction)
                    ->select($tablePrefix . 'products.*');
            });
        
        if ($brandColumn) {
            // Make sure it's visible
            $brandColumnConfig->visible(true);
        }
        
        $columns[] = $brandColumnConfig;
        
        // Add all other existing columns
        foreach ($existingColumns as $column) {
            $columnName = $column->getName();
            // Skip if we've already added it
            if (!str_contains($columnName, 'thumbnail') && 
                !str_contains($columnName, 'image') && 
                $columnName !== 'name' &&
                $columnName !== 'brand' &&
                !($column instanceof ImageColumn)) {
                $columns[] = $column;
            }
        }
        
        return $table->columns($columns);
    }
}

