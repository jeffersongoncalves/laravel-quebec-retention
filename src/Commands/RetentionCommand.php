<?php

namespace JeffersonGoncalves\QuebecRetention\Commands;

use JeffersonGoncalves\Privacy\Retention\Commands\RetentionCommand as BaseRetentionCommand;
use JeffersonGoncalves\QuebecRetention\RetentionManager;

class RetentionCommand extends BaseRetentionCommand
{
    protected $signature = 'quebec:retention {--dry-run : Only count the records that would be anonymized}';

    protected string $manager = RetentionManager::class;
}
