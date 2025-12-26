<?php

namespace App\Admin\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;

class CollectionGroupStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $groups = CollectionGroup::query()->count();
        $collections = Collection::query()->count();
        $groupsWithCollections = CollectionGroup::query()->whereHas('collections')->count();

        return [
            Stat::make('Collection groups', number_format($groups))
                ->description(number_format($groupsWithCollections).' with collections')
                ->descriptionIcon('lucide-blocks'),
            Stat::make('Collections', number_format($collections))
                ->description('Across all groups')
                ->descriptionIcon('lucide-folder-tree'),
            Stat::make('Empty groups', number_format(max($groups - $groupsWithCollections, 0)))
                ->description('Safe to clean up')
                ->descriptionIcon('lucide-broom'),
        ];
    }
}


