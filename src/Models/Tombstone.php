<?php

namespace JeffersonGoncalves\QuebecRetention\Models;

use JeffersonGoncalves\Privacy\Retention\Tombstone as BaseTombstone;

class Tombstone extends BaseTombstone
{
    public function getTable(): string
    {
        return config('quebec-retention.table', 'quebec_retention_tombstones');
    }
}
