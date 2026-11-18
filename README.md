# Laravel Quebec Retention

![Laravel Quebec Retention](https://raw.githubusercontent.com/jeffersongoncalves/laravel-quebec-retention/main/art/jeffersongoncalves-laravel-quebec-retention.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-quebec-retention.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-quebec-retention)
[![Tests](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-quebec-retention/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/jeffersongoncalves/laravel-quebec-retention/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-quebec-retention.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-quebec-retention)

Under Quebec's private sector privacy act (Law 25), once the purposes for which personal information was collected are achieved, it must be destroyed or anonymized to use it for serious and legitimate purposes (s. 23), subject to any retention period another law sets. This package reads the `retention` you declared with [laravel-quebec](https://github.com/jeffersongoncalves/laravel-quebec)'s `#[PersonalData]`. When a column's period ends, it anonymizes that column, using the column's own strategy.

- **Per column:** each column has its own period. `name` can expire after 1 year while `email` lasts 2.
- **`Keep` columns are nullified when their retention ends.** `Keep` only protects a column from destruction on the person's request while a legal duty to keep it lasts. Once the retention period is over, that duty is over too.
- **Tombstones:** the package records which column of which record it anonymized, without storing any personal data. Because of that, nothing is anonymized twice (a hash is never hashed again). And after you restore a backup, `quebec:retention-reapply` erases the data that came back.

> This package gives you tooling. It does not make you compliant on its own. Retention periods need legal review.

## Installation

```bash
composer require jeffersongoncalves/laravel-quebec-retention
php artisan vendor:publish --tag="quebec-retention-migrations"
php artisan migrate
```

## Declaring retention

```php
#[PersonalData('name', retention: '1 year', anonymize: AnonymizeStrategy::Mask)]
#[PersonalData('email', retention: '2 years', anonymize: AnonymizeStrategy::Hash)]
#[PersonalData('tax_id', retention: '5 years', anonymize: AnonymizeStrategy::Keep)] // nullified after 5 years
#[PersonalData('notes')]                                                           // no retention: never expires
class Customer extends Model implements HasPersonalData
{
    use InteractsWithPersonalData;
}
```

The period accepts any [CarbonInterval](https://carbon.nesbot.com/docs/#api-interval) string (`'90 days'`, `'5 years'`, ...). It is counted from `created_at`. To count it from another date, implement `HasRetentionStart`. Rows where that date is `null` never expire.

```php
use JeffersonGoncalves\QuebecRetention\Contracts\HasRetentionStart;

class Contract extends Model implements HasPersonalData, HasRetentionStart
{
    public static function retentionStartColumn(): string
    {
        return 'ended_at'; // counts from the end of the contract
    }
}
```

## Usage

```bash
php artisan quebec:retention --dry-run   # what would be anonymized
php artisan quebec:retention             # anonymize expired columns
php artisan quebec:retention-reapply     # after restoring a backup
```

```php
// routes/console.php
Schedule::command('quebec:retention')->daily();
```

### Destruction requested by the person

A model may be anonymized outside this package, for example by [laravel-quebec-requests](https://github.com/jeffersongoncalves/laravel-quebec-requests)'s `erase()` or by calling `$model->anonymizePersonalData()` directly. Those columns get tombstones too, so `quebec:retention-reapply` also erases them when a backup brings them back. The listener for laravel-quebec's `PersonalDataAnonymized` event records them, and you don't need to configure anything. That erasure skips `Keep` columns, so they get no tombstone, and they are still nullified when their own retention ends.

You can also call it from code: `QuebecRetention::apply(dryRun: false)` returns one row per model and column with the number of records it anonymized. `QuebecRetention::reapply()` returns how many columns it erased again.

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## License

The MIT License (MIT). See [License File](LICENSE.md).
