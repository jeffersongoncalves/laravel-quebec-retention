<?php

namespace JeffersonGoncalves\QuebecRetention\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Quebec\Attributes\PersonalData;
use JeffersonGoncalves\Quebec\Concerns\InteractsWithPersonalData;
use JeffersonGoncalves\Quebec\Contracts\HasPersonalData;
use JeffersonGoncalves\QuebecRetention\Contracts\HasRetentionStart;

#[PersonalData('signer', retention: '1 year')]
class Contract extends Model implements HasPersonalData, HasRetentionStart
{
    use InteractsWithPersonalData;

    protected $guarded = [];

    public $timestamps = false;

    public static function retentionStartColumn(): string
    {
        return 'ended_at';
    }
}
