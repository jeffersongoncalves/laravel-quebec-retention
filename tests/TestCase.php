<?php

namespace JeffersonGoncalves\QuebecRetention\Tests;

use JeffersonGoncalves\QuebecRetention\QuebecRetentionServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            QuebecRetentionServiceProvider::class,
        ];
    }
}
