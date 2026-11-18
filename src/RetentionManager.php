<?php

namespace JeffersonGoncalves\QuebecRetention;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JeffersonGoncalves\Quebec\Attributes\PersonalData;
use JeffersonGoncalves\Quebec\Contracts\HasPersonalData;
use JeffersonGoncalves\Quebec\Enums\AnonymizeStrategy;
use JeffersonGoncalves\Quebec\Quebec;
use JeffersonGoncalves\QuebecRetention\Contracts\HasRetentionStart;
use JeffersonGoncalves\QuebecRetention\Models\Tombstone;
use Throwable;

class RetentionManager
{
    public function __construct(protected Quebec $quebec) {}

    /**
     * Anonymize every column whose retention period has ended.
     *
     * @return list<array{model: class-string, column: string, retention: string, records: int}>
     */
    public function apply(bool $dryRun = false): array
    {
        $report = [];

        foreach ($this->quebec->models() as $model) {
            $start = $this->startColumn($model);

            foreach ($this->groups($model) as $retention => $fields) {
                $cutoff = now()->sub($this->interval($model, $retention));
                $records = 0;

                // ponytail: re-reads every already-expired row each run (tombstones filtered in PHP);
                // add an indexed "anonymized" marker on the model if that ever gets slow.
                $model::query()
                    ->whereNotNull($start)
                    ->where($start, '<', $cutoff)
                    ->chunkById((int) config('quebec-retention.chunk', 500), function ($chunk) use ($fields, $dryRun, &$records): void {
                        $done = $this->tombstonedKeys($chunk->first(), $fields[0]->column, $chunk->modelKeys());

                        foreach ($chunk as $record) {
                            if (isset($done[(string) $record->getKey()])) {
                                continue;
                            }

                            $records++;

                            if (! $dryRun) {
                                $this->anonymize($record, $fields);
                            }
                        }
                    });

                foreach ($fields as $field) {
                    $report[] = ['model' => $model, 'column' => $field->column, 'retention' => $retention, 'records' => $records];
                }
            }
        }

        return $report;
    }

    /**
     * Re-anonymize tombstoned columns that came back (e.g. after restoring a backup).
     *
     * @return int columns re-anonymized
     */
    public function reapply(): int
    {
        $count = 0;

        Tombstone::query()->chunkById((int) config('quebec-retention.chunk', 500), function ($tombstones) use (&$count): void {
            foreach ($tombstones->groupBy('model_type') as $type => $group) {
                $class = Relation::getMorphedModel((string) $type) ?? (string) $type;

                if (! is_subclass_of($class, Model::class) || ! is_subclass_of($class, HasPersonalData::class)) {
                    continue;
                }

                $fields = collect($class::personalDataFields())->keyBy('column');
                $records = $class::query()->findMany($group->pluck('model_id')->unique()->all())->keyBy(fn (Model $m) => (string) $m->getKey());

                foreach ($group as $tombstone) {
                    $record = $records->get($tombstone->model_id);
                    $field = $fields->get($tombstone->column);

                    // Record deleted since, column no longer declared, or still anonymized: nothing to do.
                    if (! $record instanceof Model || ! $field instanceof PersonalData
                        || $this->fingerprint($record->getAttribute($field->column)) === $tombstone->fingerprint) {
                        continue;
                    }

                    $record->setAttribute($field->column, $this->strategy($field)->apply($record->getAttribute($field->column)));
                    $record->save();
                    $count++;
                }
            }
        });

        return $count;
    }

    /**
     * @param  list<PersonalData>  $fields
     */
    protected function anonymize(Model $record, array $fields): void
    {
        DB::transaction(function () use ($record, $fields): void {
            foreach ($fields as $field) {
                $record->setAttribute($field->column, $this->strategy($field)->apply($record->getAttribute($field->column)));
            }

            $record->save();

            $this->tombstone($record, array_map(fn (PersonalData $field): string => $field->column, $fields));
        });
    }

    /**
     * Record that these columns of the record are anonymized (current values are the anonymized ones).
     *
     * @param  list<string>  $columns
     */
    public function tombstone(Model $record, array $columns): void
    {
        foreach ($columns as $column) {
            Tombstone::query()->updateOrCreate(
                ['model_type' => $record->getMorphClass(), 'model_id' => (string) $record->getKey(), 'column' => $column],
                ['fingerprint' => $this->fingerprint($record->getAttribute($column))],
            );
        }
    }

    /**
     * Keep only shields a column from destruction on a person's request (legal duty); once retention ends, the duty is over.
     */
    protected function strategy(PersonalData $field): AnonymizeStrategy
    {
        return $field->anonymize === AnonymizeStrategy::Keep ? AnonymizeStrategy::Nullify : $field->anonymize;
    }

    protected function fingerprint(mixed $value): string
    {
        return hash_hmac('sha256', (string) json_encode($value), (string) (config('quebec.hash_key') ?: config('app.key')));
    }

    /**
     * @param  class-string<Model&HasPersonalData>  $model
     * @return array<string, non-empty-list<PersonalData>> fields with a retention, grouped by period
     */
    protected function groups(string $model): array
    {
        $groups = [];

        foreach ($model::personalDataFields() as $field) {
            if ($field->retention !== null) {
                $groups[$field->retention][] = $field;
            }
        }

        return $groups;
    }

    /**
     * @param  class-string<Model>  $model
     */
    protected function startColumn(string $model): string
    {
        if (is_subclass_of($model, HasRetentionStart::class)) {
            return $model::retentionStartColumn();
        }

        return (new $model)->getCreatedAtColumn()
            ?? throw new InvalidArgumentException("{$model} has no created_at column: implement ".HasRetentionStart::class.'.');
    }

    protected function interval(string $model, string $retention): CarbonInterval
    {
        try {
            $interval = CarbonInterval::make($retention);
        } catch (Throwable) {
            $interval = null;
        }

        if (! $interval instanceof CarbonInterval || $interval->totalSeconds <= 0) {
            throw new InvalidArgumentException("Invalid retention [{$retention}] on {$model}: use a period like '5 years'.");
        }

        return $interval;
    }

    /**
     * @param  list<int|string>  $keys
     * @return array<string, true>
     */
    protected function tombstonedKeys(Model $sample, string $column, array $keys): array
    {
        $ids = Tombstone::query()
            ->where('model_type', $sample->getMorphClass())
            ->where('column', $column)
            ->whereIn('model_id', array_map('strval', $keys))
            ->get(['model_id'])
            ->map(fn (Tombstone $tombstone): string => $tombstone->model_id)
            ->all();

        return array_fill_keys($ids, true);
    }
}
