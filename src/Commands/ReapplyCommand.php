<?php

namespace JeffersonGoncalves\QuebecRetention\Commands;

use JeffersonGoncalves\Privacy\Retention\Commands\ReapplyCommand as BaseReapplyCommand;
use JeffersonGoncalves\QuebecRetention\RetentionManager;

class ReapplyCommand extends BaseReapplyCommand
{
    protected $signature = 'quebec:retention-reapply';

    protected string $manager = RetentionManager::class;
}
