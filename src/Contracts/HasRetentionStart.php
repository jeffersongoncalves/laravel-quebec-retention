<?php

namespace JeffersonGoncalves\QuebecRetention\Contracts;

/**
 * Count the retention period from another date column than created_at
 * (e.g. the end of the contract or the account closing date). Rows where it is null never expire.
 */
interface HasRetentionStart
{
    public static function retentionStartColumn(): string;
}
