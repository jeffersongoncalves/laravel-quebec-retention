<?php

namespace JeffersonGoncalves\QuebecRetention;

use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\Quebec\Events\PersonalDataAnonymized;
use JeffersonGoncalves\QuebecRetention\Commands\ReapplyCommand;
use JeffersonGoncalves\QuebecRetention\Commands\RetentionCommand;
use JeffersonGoncalves\QuebecRetention\Listeners\RecordTombstones;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class QuebecRetentionServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('quebec-retention')
            ->hasConfigFile()
            ->hasMigrations(['create_quebec_retention_tombstones_table'])
            ->hasCommands([RetentionCommand::class, ReapplyCommand::class]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RetentionManager::class);
    }

    public function packageBooted(): void
    {
        Event::listen(PersonalDataAnonymized::class, RecordTombstones::class);
    }
}
