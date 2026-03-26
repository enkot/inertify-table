<?php

declare(strict_types=1);

namespace Inertify\Table;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Sorts\Sort as SpatieSort;
use Spatie\QueryBuilder\Sorts\SortsCallback;
use Spatie\QueryBuilder\Sorts\SortsField;

class Sort implements SpatieSort
{
    public function __construct(
        public string $key,
        public ?string $label = null,
        public string $column = '',
        private ?SpatieSort $internalSort = null
    ) {
        if ($this->column === '') {
            $this->column = $this->key;
        }
    }

    public static function field(string $key, ?string $column = null, ?string $label = null): self
    {
        return new self(
            key: $key,
            label: $label,
            column: $column ?? $key,
            internalSort: new SortsField()
        );
    }

    public static function callback(string $key, Closure $callback, ?string $label = null): self
    {
        return new self(
            key: $key,
            label: $label,
            column: $key,
            internalSort: new SortsCallback($callback)
        );
    }

    public static function custom(string $key, SpatieSort|Closure $sort, ?string $label = null): self
    {
        if ($sort instanceof Closure) {
            return self::callback($key, $sort, $label);
        }

        return new self(
            key: $key,
            label: $label,
            column: $key,
            internalSort: $sort
        );
    }

    public function __invoke(EloquentBuilder $query, bool $descending, string $property): void
    {
        $this->apply($query, $descending, $property);
    }

    /**
     * @param EloquentBuilder|BaseBuilder $query
     */
    public function apply(EloquentBuilder|BaseBuilder $query, bool $descending, string $property): void
    {
        if ($this->internalSort !== null) {
            ($this->internalSort)($query, $descending, $property);

            return;
        }

        $sort = new SortsField();
        $sort($query, $descending, $property);
    }
}
