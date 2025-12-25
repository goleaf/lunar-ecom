<?php

namespace App\Admin\Extensions\Pages;

use Filament\Actions;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Lunar\Admin\Support\Extending\ListPageExtension;

/**
 * Example extension for List pages in Lunar admin panel.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/admin/extending/pages#listpageextension
 * 
 * Extensions allow you to customize the behavior and appearance of Lunar's
 * admin panel list pages, such as adding custom widgets, actions, or modifying
 * the query pagination.
 */
class ExampleListPageExtension extends ListPageExtension
{
    /**
     * Customize the page heading.
     */
    public function heading($title): string
    {
        return $title . ' - Example';
    }

    /**
     * Customize the page subheading.
     */
    public function subheading($title): string
    {
        return $title . ' - Example';
    }

    /**
     * Add or modify header widgets.
     */
    public function headerWidgets(array $widgets): array
    {
        return [
            ...$widgets,
            // Add custom widgets here
        ];
    }

    /**
     * Add or modify header actions.
     */
    public function headerActions(array $actions): array
    {
        return [
            ...$actions,
            // Add custom header actions here
            // Actions\ActionGroup::make([
            //     Actions\Action::make('Bulk Export'),
            //     Actions\Action::make('Bulk Import'),
            // ]),
        ];
    }

    /**
     * Customize the table query pagination.
     */
    public function paginateTableQuery(Builder $query, int $perPage = 25): Paginator
    {
        // Custom pagination logic
        return $query->paginate($perPage);
    }

    /**
     * Add or modify footer widgets.
     */
    public function footerWidgets(array $widgets): array
    {
        return [
            ...$widgets,
            // Add custom footer widgets here
        ];
    }
}


