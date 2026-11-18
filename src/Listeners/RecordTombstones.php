<?php

namespace JeffersonGoncalves\QuebecRetention\Listeners;

use JeffersonGoncalves\Privacy\Retention\Listeners\RecordTombstones as BaseRecordTombstones;
use JeffersonGoncalves\QuebecRetention\RetentionManager;

/**
 * Tombstones any anonymization of the suite, so the retention-reapply command covers it too.
 */
class RecordTombstones extends BaseRecordTombstones
{
    public function __construct(RetentionManager $retention)
    {
        parent::__construct($retention);
    }
}
