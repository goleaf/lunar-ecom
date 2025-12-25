<?php

namespace App\Admin\Extensions\Pages;

use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Support\Extending\CreatePageExtension;

/**
 * Example extension for Create pages in Lunar admin panel.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/admin/extending/pages#createpageextension
 * 
 * Extensions allow you to customize the behavior and appearance of Lunar's
 * admin panel pages, such as adding custom widgets, actions, or modifying
 * form data during creation.
 */
class ExampleCreatePageExtension extends CreatePageExtension
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
            // \Lunar\Admin\Filament\Widgets\Dashboard\Orders\OrderStatsOverview::make(),
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
            // Actions\Action::make('Cancel')
            //     ->label('Cancel')
            //     ->color('gray')
            //     ->action(fn () => redirect()->back()),
        ];
    }

    /**
     * Add or modify form actions.
     */
    public function formActions(array $actions): array
    {
        return [
            ...$actions,
            // Add custom form actions here
            // Actions\Action::make('Create and Edit')
            //     ->label('Create and Edit')
            //     ->action(function () {
            //         // Custom action logic
            //     }),
        ];
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

    /**
     * Modify data before creation (before validation).
     */
    public function beforeCreate(array $data): array
    {
        // Modify data before validation
        // Example: $data['model_code'] .= 'ABC';
        return $data;
    }

    /**
     * Modify data before creation (after validation).
     */
    public function beforeCreation(array $data): array
    {
        // Modify data after validation but before saving
        return $data;
    }

    /**
     * Handle the record after creation.
     */
    public function afterCreation(Model $record, array $data): Model
    {
        // Perform actions after the record is created
        // Example: Log the creation, send notifications, etc.
        return $record;
    }
}


