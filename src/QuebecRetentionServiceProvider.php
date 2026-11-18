<?php

namespace JeffersonGoncalves\QuebecRetention;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class QuebecRetentionServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-quebec-retention')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }
}
