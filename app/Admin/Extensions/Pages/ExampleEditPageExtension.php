<?php

namespace App\Admin\Extensions\Pages;

use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Support\Extending\EditPageExtension;

/**
 * Example extension for Edit pages in Lunar admin panel.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/admin/extending/pages#editpageextension
 * 
 * Extensions allow you to customize the behavior and appearance of Lunar's
 * admin panel pages, such as adding custom widgets, actions, or modifying
 * form data during updates.
 */
class ExampleEditPageExtension extends EditPageExtension
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
            //     Actions\Action::make('View on Storefront'),
            //     Actions\Action::make('Copy Link'),
            //     Actions\Action::make('Duplicate'),
            // ]),
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
     * Modify data before filling the form.
     */
    public function beforeFill(array $data): array
    {
        // Modify data before it's used to fill the form
        return $data;
    }

    /**
     * Modify data before saving (before validation).
     */
    public function beforeSave(array $data): array
    {
        // Modify data before validation
        return $data;
    }

    /**
     * Modify data before updating (after validation).
     */
    public function beforeUpdate(array $data, Model $record): array
    {
        // Modify data after validation but before saving
        return $data;
    }

    /**
     * Handle the record after update.
     */
    public function afterUpdate(Model $record, array $data): Model
    {
        // Perform actions after the record is updated
        return $record;
    }

    /**
     * Modify relation managers.
     */
    public function relationManagers(array $managers): array
    {
        return $managers;
    }
}


