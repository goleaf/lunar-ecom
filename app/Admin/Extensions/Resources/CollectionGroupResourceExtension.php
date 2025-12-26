<?php

namespace App\Admin\Extensions\Resources;

use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Lunar\Admin\Filament\Resources\CollectionGroupResource;
use Lunar\Admin\Support\Actions\Collections\CreateRootCollection;
use Lunar\Admin\Support\Extending\ResourceExtension;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;

class CollectionGroupResourceExtension extends ResourceExtension
{
    public function extendTable(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (CollectionGroup $record) => $record->handle, position: 'below')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('handle')
                    ->label('Handle')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Handle copied')
                    ->copyMessageDuration(1500)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('collections_count')
                    ->label('Collections')
                    ->counts('collections')
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => number_format((int) $state))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('has_collections')
                    ->label('Has collections')
                    ->placeholder('Any')
                    ->trueLabel('With collections')
                    ->falseLabel('Empty')
                    ->queries(
                        true: fn ($query) => $query->whereHas('collections'),
                        false: fn ($query) => $query->whereDoesntHave('collections'),
                        blank: fn ($query) => $query
                    ),

                Tables\Filters\Filter::make('updated_recently')
                    ->label('Updated recently')
                    ->query(fn ($query) => $query->where('updated_at', '>=', now()->subDays(14))),
            ])
            ->actions([
                Tables\Actions\Action::make('openTree')
                    ->label('Open')
                    ->icon('lucide-folder-tree')
                    ->url(fn (CollectionGroup $record) => CollectionGroupResource::getUrl('edit', ['record' => $record])),

                CreateRootCollection::make('createRootCollection')
                    ->label('Add root collection')
                    ->icon(fn () => \Filament\Support\Facades\FilamentIcon::resolve('lunar::sub-collection'))
                    ->mutateFormDataUsing(function (array $data, CollectionGroup $record) {
                        $data['collection_group_id'] = $record->id;
                        return $data;
                    })
                    ->after(function ($record) {
                        // $record is the created Collection instance (from CreateRootCollection::setUp()).
                        if ($record instanceof Collection) {
                            Notification::make()
                                ->title('Collection created')
                                ->body("Created '{$record->attr('name')}'.")
                                ->success()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('lucide-copy')
                    ->requiresConfirmation()
                    ->action(function (CollectionGroup $record) {
                        $baseName = (string) $record->name;
                        $baseHandle = (string) $record->handle;

                        $copy = $record->replicate(['created_at', 'updated_at']);
                        $copy->name = Str::limit($baseName.' (Copy)', 255, '');

                        $candidate = Str::limit($baseHandle.'-copy', 255, '');
                        $handle = $candidate;
                        $i = 2;
                        while (CollectionGroup::query()->where('handle', $handle)->exists()) {
                            $handle = Str::limit($candidate.'-'.$i, 255, '');
                            $i++;
                        }
                        $copy->handle = $handle;
                        $copy->save();

                        Notification::make()
                            ->title('Collection group duplicated')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->before(function (CollectionGroup $record, Tables\Actions\DeleteAction $action) {
                        if ($record->collections()->count() > 0) {
                            Notification::make()
                                ->warning()
                                ->title('Cannot delete')
                                ->body('This collection group has collections. Move or delete them first.')
                                ->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('exportCsv')
                        ->label('Export CSV')
                        ->icon('lucide-download')
                        ->action(function (Tables\Actions\BulkAction $action, $records) {
                            $filename = 'collection-groups-'.now()->format('Y-m-d_His').'.csv';

                            return response()->streamDownload(function () use ($records) {
                                $out = fopen('php://output', 'w');
                                fputcsv($out, ['id', 'name', 'handle', 'collections_count', 'created_at', 'updated_at']);

                                foreach ($records as $record) {
                                    /** @var CollectionGroup $record */
                                    fputcsv($out, [
                                        $record->id,
                                        (string) $record->name,
                                        (string) $record->handle,
                                        (int) $record->collections()->count(),
                                        optional($record->created_at)->toDateTimeString(),
                                        optional($record->updated_at)->toDateTimeString(),
                                    ]);
                                }

                                fclose($out);
                            }, $filename, [
                                'Content-Type' => 'text/csv; charset=UTF-8',
                            ]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deleteEmpty')
                        ->label('Delete empty groups')
                        ->icon('lucide-trash-2')
                        ->requiresConfirmation()
                        ->color('danger')
                        ->action(function ($records) {
                            $deleted = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                /** @var CollectionGroup $record */
                                if ($record->collections()->count() > 0) {
                                    $skipped++;
                                    continue;
                                }
                                $record->delete();
                                $deleted++;
                            }

                            Notification::make()
                                ->title('Bulk delete complete')
                                ->body("Deleted {$deleted} empty groups. Skipped {$skipped} non-empty groups.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}


