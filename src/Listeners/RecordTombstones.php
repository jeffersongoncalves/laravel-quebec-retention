<?php

namespace JeffersonGoncalves\QuebecRetention\Listeners;

use JeffersonGoncalves\Quebec\Events\PersonalDataAnonymized;
use JeffersonGoncalves\QuebecRetention\RetentionManager;

/**
 * Tombstones any anonymization (DSAR erasure, consent records...), so quebec:retention-reapply covers it too.
 */
class RecordTombstones
{
    public function __construct(protected RetentionManager $retention) {}

    public function handle(PersonalDataAnonymized $event): void
    {
        $this->retention->tombstone($event->model, $event->columns);
    }
}
