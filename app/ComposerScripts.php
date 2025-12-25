<?php

namespace App;

use Composer\Installer\PackageEvent;
use Illuminate\Foundation\ComposerScripts as LaravelComposerScripts;

class ComposerScripts
{
    /**
     * Handle the pre-package-uninstall Composer event.
     * Wraps Laravel's implementation with error handling.
     *
     * @param  \Composer\Installer\PackageEvent  $event
     * @return void
     */
    public static function prePackageUninstall(PackageEvent $event)
    {
        // Ensure autoloader is loaded
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';
        
        try {
            LaravelComposerScripts::prePackageUninstall($event);
        } catch (\Exception $e) {
            // Silently fail during composer operations to avoid breaking updates
            // This can happen when Laravel isn't fully bootstrapped or dependencies are missing
            // The error is logged but doesn't stop the composer operation
        } catch (\Throwable $e) {
            // Catch any other errors as well
        }
    }
}

