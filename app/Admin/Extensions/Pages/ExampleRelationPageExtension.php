<?php

namespace App\Admin\Extensions\Pages;

use Filament\Actions;
use Lunar\Admin\Support\Extending\RelationPageExtension;

/**
 * Example extension for Relation pages in Lunar admin panel.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/admin/extending/pages#relationpageextension
 * 
 * Extensions allow you to customize the behavior and appearance of Lunar's
 * admin panel relation pages, such as adding custom actions or modifying
 * the page appearance.
 */
class ExampleRelationPageExtension extends RelationPageExtension
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
     * Add or modify header actions.
     */
    public function headerActions(array $actions): array
    {
        return [
            ...$actions,
            // Add custom header actions here
            // Actions\ActionGroup::make([
            //     Actions\Action::make('Download PDF'),
            //     Actions\Action::make('Export'),
            // ]),
        ];
    }
}


