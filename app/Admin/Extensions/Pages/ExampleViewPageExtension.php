<?php

namespace App\Admin\Extensions\Pages;

use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Lunar\Admin\Support\Extending\ViewPageExtension;

/**
 * Example extension for View pages in Lunar admin panel.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/admin/extending/pages#viewpageextension
 * 
 * Extensions allow you to customize the behavior and appearance of Lunar's
 * admin panel view pages, such as adding custom widgets, actions, or modifying
 * the infolist schema.
 */
class ExampleViewPageExtension extends ViewPageExtension
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
            //     Actions\Action::make('Download PDF'),
            //     Actions\Action::make('Print'),
            // ]),
        ];
    }

    /**
     * Extend the infolist schema.
     */
    public function extendsInfolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            ...$infolist->getComponents(true), // Gets all currently registered components
            // Add custom infolist entries here
            // TextEntry::make('custom_title')
            //     ->label('Custom Title'),
        ]);
    }

    /**
     * Extend the form schema (for addons that can't assume form contents).
     */
    public function extendForm(Form $form): Form
    {
        $form->schema([
            ...$form->getComponents(true), // Gets all currently registered components
            // Add custom form fields here
            // TextInput::make('custom_field')
            //     ->label('Custom Field'),
        ]);
        return $form;
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


