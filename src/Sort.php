<?php

declare(strict_types=1);

namespace Taras\InertiaHeadlessTable;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Str;

class Sort
{
    public function __construct(
        public string $key,
        public string $label,
        public string $column,
        public ?Closure $callback = null
    ) {
    }

    public static function make(string $key, ?string $column = null, ?string $label = null): self
    {
        return new self(
            key: $key,
            label: $label ?? Str::headline(str_replace('.', ' ', $key)),
            column: $column ?? $key,
        );
    }

    public static function callback(string $key, Closure $callback, ?string $label = null): self
    {
        return new self(
            key: $key,
            label: $label ?? Str::headline(str_replace('.', ' ', $key)),
            column: $key,
            callback: $callback
        );
    }

    /**
     * @param EloquentBuilder|BaseBuilder $query
     */
    public function apply(EloquentBuilder|BaseBuilder $query, string $direction): void
    {
        if ($this->callback) {
            ($this->callback)($query, $direction, $this);

            return;
        }

        $query->orderBy($this->column, $direction);
    }

    public function toArray(?string $activeDirection = null): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'column' => $this->column,
            'direction' => $activeDirection,
        ];
    }
}
