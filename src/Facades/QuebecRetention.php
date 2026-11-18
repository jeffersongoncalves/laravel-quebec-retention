<?php

namespace JeffersonGoncalves\QuebecRetention\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JeffersonGoncalves\QuebecRetention\QuebecRetention
 */
class QuebecRetention extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-quebec-retention';
    }
}
