<?php

namespace App\Admin\Extensions\Pages;

use App\Admin\Filament\Widgets\CollectionGroupStatsOverview;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Lunar\Admin\Support\Extending\ListPageExtension;
use Lunar\Models\CollectionGroup;

class CollectionGroupListPageExtension extends ListPageExtension
{
    public function heading($title): string
    {
        return 'Collection Groups';
    }

    public function subheading($title): ?string
    {
        return 'Organize collections into groups. Use “Open” to manage the collection tree.';
    }

    public function headerWidgets(array $widgets): array
    {
        return [
            CollectionGroupStatsOverview::class,
            ...$widgets,
        ];
    }

    public function headerActions(array $actions): array
    {
        return [
            ...$actions,

            Actions\Action::make('quickCreate')
                ->label('Quick create')
                ->icon('lucide-plus')
                ->form([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                            $set('handle', Str::slug((string) $state));
                        }),
                    \Filament\Forms\Components\TextInput::make('handle')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data) {
                    $handle = (string) $data['handle'];
                    $candidate = $handle;
                    $i = 2;
                    while (CollectionGroup::query()->where('handle', $candidate)->exists()) {
                        $candidate = Str::limit($handle.'-'.$i, 255, '');
                        $i++;
                    }

                    CollectionGroup::create([
                        'name' => (string) $data['name'],
                        'handle' => $candidate,
                    ]);

                    Notification::make()
                        ->title('Collection group created')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTabs(array $tabs): array
    {
        return [
            'all' => Tab::make('All'),

            'with_collections' => Tab::make('With collections')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('collections')),

            'empty' => Tab::make('Empty')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDoesntHave('collections')),

            'recent' => Tab::make('Recently updated')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('updated_at', '>=', now()->subDays(7))),
        ];
    }
}


