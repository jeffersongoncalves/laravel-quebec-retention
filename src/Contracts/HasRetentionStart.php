<?php

namespace JeffersonGoncalves\QuebecRetention\Contracts;

use JeffersonGoncalves\Privacy\Retention\Contracts\HasRetentionStart as BaseHasRetentionStart;

/**
 * Count the retention period from another date column than created_at
 * (e.g. the end of the contract or the account closing date). Rows where it is null never expire.
 */
interface HasRetentionStart extends BaseHasRetentionStart {}
