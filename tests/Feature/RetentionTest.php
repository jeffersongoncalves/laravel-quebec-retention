<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\Quebec\Attributes\PersonalData;
use JeffersonGoncalves\QuebecRetention\Facades\QuebecRetention;
use JeffersonGoncalves\QuebecRetention\Models\Tombstone;
use JeffersonGoncalves\QuebecRetention\Tests\Fixtures\Contract;
use JeffersonGoncalves\QuebecRetention\Tests\Fixtures\Customer;

function customer(): Customer
{
    return Customer::create(['name' => 'Mary', 'email' => 'mary@example.com', 'tax_id' => '123', 'notes' => 'vip']);
}

it('anonymizes each column only once its own period has ended', function () {
    $customer = customer();

    $this->travel(13)->months();
    QuebecRetention::apply();
    $customer->refresh();

    expect($customer->name)->toBe('M***')
        ->and($customer->email)->toBe('mary@example.com');

    $this->travel(12)->months();
    QuebecRetention::apply();
    $customer->refresh();

    expect($customer->email)->toBe(hash_hmac('sha256', 'mary@example.com', config('app.key')))
        ->and($customer->tax_id)->toBe('123')
        ->and($customer->notes)->toBe('vip'); // no retention declared
});

it('nullifies Keep columns when their retention ends', function () {
    $customer = customer();

    $this->travel(61)->months();
    QuebecRetention::apply();

    expect($customer->fresh()->tax_id)->toBeNull();
});

it('never anonymizes the same column twice', function () {
    $customer = customer();
    $this->travel(3)->years();

    QuebecRetention::apply();
    $hash = $customer->fresh()->email;
    $report = QuebecRetention::apply();

    expect($customer->fresh()->email)->toBe($hash)
        ->and(collect($report)->sum('records'))->toBe(0)
        ->and(Tombstone::count())->toBe(2);
});

it('counts without touching anything on a dry run', function () {
    customer();
    $this->travel(13)->months();

    Artisan::call('quebec:retention', ['--dry-run' => true]);

    expect(Artisan::output())->toContain('Would anonymize')
        ->and(Customer::first()->name)->toBe('Mary')
        ->and(Tombstone::count())->toBe(0);
});

it('counts from a custom start column and skips rows where it is null', function () {
    $ended = Contract::create(['signer' => 'Ana', 'ended_at' => now()]);
    $running = Contract::create(['signer' => 'Bia', 'ended_at' => null]);

    $this->travel(13)->months();
    QuebecRetention::apply();

    expect($ended->fresh()->signer)->toBeNull()
        ->and($running->fresh()->signer)->toBe('Bia');
});

it('re-anonymizes data restored from a backup, once', function () {
    $customer = customer();
    $this->travel(3)->years();
    QuebecRetention::apply();
    $anonymized = $customer->fresh()->only('name', 'email');

    // Backup restore brings the original values back.
    DB::table('customers')->where('id', $customer->id)->update(['name' => 'Mary', 'email' => 'mary@example.com']);

    expect(QuebecRetention::reapply())->toBe(2)
        ->and($customer->fresh()->only('name', 'email'))->toBe($anonymized)
        ->and(QuebecRetention::reapply())->toBe(0);
});

it('tombstones any anonymization (e.g. destruction on request) so a backup restore is erased again', function () {
    $customer = customer();
    $customer->anonymizePersonalData(); // what laravel-quebec-dsar erase() does
    $erased = $customer->fresh()->only('name', 'email', 'notes');

    expect(Tombstone::pluck('column')->sort()->values()->all())->toBe(['email', 'name', 'notes']); // not tax_id (Keep)

    DB::table('customers')->where('id', $customer->id)->update(['name' => 'Mary', 'email' => 'mary@example.com', 'notes' => 'vip']);

    expect(QuebecRetention::reapply())->toBe(3)
        ->and($customer->fresh()->only('name', 'email', 'notes'))->toBe($erased);

    // The Keep column the erasure spared still expires with its retention; erased columns are not touched again.
    $this->travel(61)->months();
    QuebecRetention::apply();

    expect($customer->fresh()->tax_id)->toBeNull()
        ->and($customer->fresh()->email)->toBe($erased['email']);
});

it('rejects an invalid retention period', function () {
    config()->set('quebec.models', [BrokenRetention::class]);

    QuebecRetention::apply();
})->throws(InvalidArgumentException::class, 'Invalid retention');

#[PersonalData('name', retention: 'forever')]
class BrokenRetention extends Customer
{
    protected $table = 'customers';
}
