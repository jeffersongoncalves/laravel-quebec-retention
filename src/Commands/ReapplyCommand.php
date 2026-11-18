<?php

namespace JeffersonGoncalves\QuebecRetention\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\QuebecRetention\RetentionManager;

class ReapplyCommand extends Command
{
    protected $signature = 'quebec:retention-reapply';

    protected $description = 'Re-anonymize tombstoned personal data that came back, e.g. after restoring a backup';

    public function handle(RetentionManager $retention): int
    {
        $this->info($retention->reapply().' column(s) re-anonymized.');

        return self::SUCCESS;
    }
}
