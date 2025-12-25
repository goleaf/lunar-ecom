<?php

namespace App\Admin\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * Example Filament plugin for Lunar admin panel.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/admin/extending/addons
 * https://filamentphp.com/docs/3.x/panels/plugins
 * 
 * Filament plugins allow you to add additional resources, pages, and widgets
 * to the Lunar admin panel. This is particularly useful when creating addon
 * packages for Lunar.
 * 
 * Note: Addon packages should NOT automatically register plugins. Instead,
 * installation instructions should be provided for manual registration.
 * 
 * Plugins can:
 * - Add resources via ->discoverResources() or ->resources()
 * - Add pages via ->discoverPages() or ->pages()
 * - Add widgets via ->discoverWidgets() or ->widgets()
 * - Configure navigation, branding, and other panel settings
 */
class ExamplePlugin implements Plugin
{
    /**
     * Configure the panel for this plugin.
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->discoverResources(
                in: app_path('Admin/Plugins/ExamplePlugin/Resources'),
                for: 'App\\Admin\\Plugins\\ExamplePlugin\\Resources'
            )
            ->discoverPages(
                in: app_path('Admin/Plugins/ExamplePlugin/Pages'),
                for: 'App\\Admin\\Plugins\\ExamplePlugin\\Pages'
            )
            ->discoverWidgets(
                in: app_path('Admin/Plugins/ExamplePlugin/Widgets'),
                for: 'App\\Admin\\Plugins\\ExamplePlugin\\Widgets'
            );
    }

    /**
     * Get the plugin ID.
     */
    public function getId(): string
    {
        return 'example-plugin';
    }
}
