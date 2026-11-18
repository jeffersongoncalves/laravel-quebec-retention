<?php

namespace JeffersonGoncalves\QuebecRetention;

use JeffersonGoncalves\Privacy\Retention\RetentionManager as BaseRetentionManager;
use JeffersonGoncalves\Quebec\Quebec;
use JeffersonGoncalves\QuebecRetention\Models\Tombstone;

/**
 * Anonymizes personal data whose retention period has ended, tombstones every anonymization of the suite and
 * re-applies them after a backup restore.
 */
class RetentionManager extends BaseRetentionManager
{
    protected string $config = 'quebec-retention';

    protected string $tombstone = Tombstone::class;

    public function __construct(Quebec $quebec)
    {
        parent::__construct($quebec);
    }
}
