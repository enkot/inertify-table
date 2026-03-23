<?php

declare(strict_types=1);

namespace Inertify\Table;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\Filter as SpatieFilter;
use Spatie\QueryBuilder\Filters\FiltersCallback;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\Filters\FiltersPartial;

class Filter implements SpatieFilter
{
    private const MATCH_PARTIAL = 'partial';
    private const MATCH_EXACT = 'exact';
    private const MATCH_CALLBACK = 'callback';
    private const MATCH_NUMBER_RANGE = 'number_range';
    private const MATCH_DATE_RANGE = 'date_range';
    private const OPERATOR_IS = 'is';
    private const OPERATOR_IS_NOT = 'is_not';
    private const OPERATOR_CONTAINS = 'contains';
    private const OPERATOR_GREATER_THAN = 'greater_than';
    private const OPERATOR_LESS_THAN = 'less_than';
    private const OPERATOR_HAS_ANY_VALUE = 'has_any_value';

    public function __construct(
        public string $key,
        public string $label,
        public string $column,
        public string $input = 'text',
        public string $match = self::MATCH_PARTIAL,
        public array $options = [],
        public bool $multiple = false,
        public mixed $default = null,
        public ?Closure $callback = null,
        public int|float|string|null $rangeMin = null,
        public int|float|string|null $rangeMax = null,
        public int|float|null $rangeStep = null
    ) {}

    public static function partial(string $key, ?string $column = null, ?string $label = null): self
    {
        return new self(
            key: $key,
            label: $label ?? Str::headline(str_replace('.', ' ', $key)),
            column: $column ?? $key,
            input: 'text',
            match: self::MATCH_PARTIAL
        );
    }

    public static function exact(string $key, ?string $column = null, ?string $label = null): self
    {
        return new self(
            key: $key,
            label: $label ?? Str::headline(str_replace('.', ' ', $key)),
            column: $column ?? $key,
            input: 'text',
            match: self::MATCH_EXACT
        );
    }

    public static function select(
        string $key,
        array $options,
        ?string $column = null,
        ?string $label = null,
        bool $multiple = false
    ): self {
        return new self(
            key: $key,
            label: $label ?? Str::headline(str_replace('.', ' ', $key)),
            column: $column ?? $key,
            input: 'select',
            match: self::MATCH_EXACT,
            options: $options,
            multiple: $multiple
        );
    }

    public static function callback(
        string $key,
        Closure $callback,
        ?string $label = null,
        string $input = 'text'
    ): self {
        return new self(
            key: $key,
            label: $label ?? Str::headline(str_replace('.', ' ', $key)),
            column: $key,
            input: $input,
            match: self::MATCH_CALLBACK,
            callback: $callback
        );
    }

    public static function numberRange(
        string $key,
        ?string $column = null,
        ?string $label = null,
        int|float|string|null $min = null,
        int|float|string|null $max = null,
        int|float|null $step = null
    ): self {
        return new self(
            key: $key,
            label: $label ?? Str::headline(str_replace('.', ' ', $key)),
            column: $column ?? $key,
            input: 'number-range',
            match: self::MATCH_NUMBER_RANGE,
            rangeMin: $min,
            rangeMax: $max,
            rangeStep: $step
        );
    }

    public static function dateRange(
        string $key,
        ?string $column = null,
        ?string $label = null,
        ?string $min = null,
        ?string $max = null
    ): self {
        return new self(
            key: $key,
            label: $label ?? Str::headline(str_replace('.', ' ', $key)),
            column: $column ?? $key,
            input: 'date-range',
            match: self::MATCH_DATE_RANGE,
            rangeMin: $min,
            rangeMax: $max
        );
    }

    public function __invoke(\Illuminate\Database\Eloquent\Builder $query, mixed $value, string $property): void
    {
        $this->apply($query, $value);
    }

    /**
     * @param EloquentBuilder|BaseBuilder $query
     */
    public function apply(EloquentBuilder|BaseBuilder $query, mixed $value): void
    {
        $value = $this->normalizeLegacySingleRuleValue($value);

        if ($this->isBlank($value)) {
            return;
        }

        if ($this->match === self::MATCH_NUMBER_RANGE) {
            $this->applyNumberRange($query, $value);

            return;
        }

        if ($this->match === self::MATCH_DATE_RANGE) {
            $this->applyDateRange($query, $value);

            return;
        }

        if ($this->match === self::MATCH_CALLBACK && $this->callback) {
            (new FiltersCallback($this->callback))($query, $value, $this->column);

            return;
        }

        if ($this->match === self::MATCH_EXACT) {
            $this->applyExact($query, $value);

            return;
        }

        $this->applyPartial($query, $value);
    }

    private function normalizeLegacySingleRuleValue(mixed $value): mixed
    {
        if (!is_array($value) || !array_key_exists('rules', $value)) {
            return $value;
        }

        $rules = Arr::get($value, 'rules', []);

        if (!is_array($rules)) {
            return $value;
        }

        $pendingOperator = null;

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            if (array_key_exists('operator', $rule) && array_key_exists('value', $rule)) {
                return [
                    'operator' => Arr::get($rule, 'operator'),
                    'value' => Arr::get($rule, 'value'),
                ];
            }

            if (array_key_exists('operator', $rule) && !array_key_exists('value', $rule)) {
                $pendingOperator = Arr::get($rule, 'operator');

                continue;
            }

            if ($pendingOperator !== null && array_key_exists('value', $rule)) {
                return [
                    'operator' => $pendingOperator,
                    'value' => Arr::get($rule, 'value'),
                ];
            }

            if (array_key_exists('value', $rule)) {
                return [
                    'operator' => self::OPERATOR_CONTAINS,
                    'value' => Arr::get($rule, 'value'),
                ];
            }
        }

        return $value;
    }

    public function withDefault(mixed $value): self
    {
        $this->default = $value;

        return $this;
    }

    public function withOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function multiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function toArray(mixed $currentValue = null): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'column' => $this->column,
            'input' => $this->input,
            'multiple' => $this->multiple,
            'options' => $this->options,
            'default' => $this->default,
            'value' => $currentValue,
            'rangeMin' => $this->rangeMin,
            'rangeMax' => $this->rangeMax,
            'rangeStep' => $this->rangeStep,
        ];
    }

    /**
     * @param EloquentBuilder|BaseBuilder $query
     */
    private function applyNumberRange(EloquentBuilder|BaseBuilder $query, mixed $value): void
    {
        [$operator, $from, $to] = $this->extractRangeOperatorAndBounds(
            $value,
            fn(mixed $bound): mixed => is_numeric($bound) ? $bound : null,
        );

        $this->applyRangeOperator($query, $operator, $from, $to);
    }

    /**
     * @param EloquentBuilder|BaseBuilder $query
     */
    private function applyDateRange(EloquentBuilder|BaseBuilder $query, mixed $value): void
    {
        [$operator, $from, $to] = $this->extractRangeOperatorAndBounds(
            $value,
            fn(mixed $bound): mixed => $this->isBlank($bound) ? null : (string) $bound,
        );

        $this->applyRangeOperator($query, $operator, $from, $to);
    }

    /**
     * @param EloquentBuilder|BaseBuilder $query
     */
    private function applyRangeBounds(EloquentBuilder|BaseBuilder $query, mixed $from, mixed $to): void
    {
        if ($from !== null && $to !== null) {
            $query->whereBetween($this->column, [$from, $to]);

            return;
        }

        if ($from !== null) {
            $query->where($this->column, '>=', $from);
        }

        if ($to !== null) {
            $query->where($this->column, '<=', $to);
        }
    }

    /**
     * @param EloquentBuilder|BaseBuilder $query
     */
    private function applyRangeOperator(EloquentBuilder|BaseBuilder $query, string $operator, mixed $from, mixed $to): void
    {
        if ($operator === self::OPERATOR_HAS_ANY_VALUE) {
            $query->whereNotNull($this->column);
            $query->where($this->column, '!=', '');

            return;
        }

        if ($operator === self::OPERATOR_GREATER_THAN) {
            $bound = $from ?? $to;

            if ($bound !== null) {
                $query->where($this->column, '>', $bound);
            }

            return;
        }

        if ($operator === self::OPERATOR_LESS_THAN) {
            $bound = $to ?? $from;

            if ($bound !== null) {
                $query->where($this->column, '<', $bound);
            }

            return;
        }

        if ($operator === self::OPERATOR_IS_NOT) {
            if ($from !== null && $to !== null) {
                $query->whereNotBetween($this->column, [$from, $to]);

                return;
            }

            if ($from !== null) {
                $query->where($this->column, '<', $from);

                return;
            }

            if ($to !== null) {
                $query->where($this->column, '>', $to);
            }

            return;
        }

        $this->applyRangeBounds($query, $from, $to);
    }

    /**
     * @param EloquentBuilder|BaseBuilder $query
     */
    private function applyExact(EloquentBuilder|BaseBuilder $query, mixed $value): void
    {
        [$operator, $operand] = $this->extractOperatorAndOperand($value, self::OPERATOR_IS);

        if ($operator === self::OPERATOR_HAS_ANY_VALUE) {
            $query->whereNotNull($this->column);
            $query->where($this->column, '!=', '');

            return;
        }

        if ($operator === self::OPERATOR_IS_NOT) {
            if (is_array($operand)) {
                $query->whereNotIn($this->column, $operand);

                return;
            }

            $query->where($this->column, '!=', $this->normalize($operand));

            return;
        }

        if ($operator === self::OPERATOR_CONTAINS) {
            (new FiltersPartial())($query, $operand, $this->column);

            return;
        }

        (new FiltersExact())($query, is_array($operand) ? $operand : $this->normalize($operand), $this->column);
    }

    /**
     * @param EloquentBuilder|BaseBuilder $query
     */
    private function applyPartial(EloquentBuilder|BaseBuilder $query, mixed $value): void
    {
        [$operator, $operand] = $this->extractOperatorAndOperand($value, self::OPERATOR_CONTAINS);

        if ($operator === self::OPERATOR_HAS_ANY_VALUE) {
            $query->whereNotNull($this->column);
            $query->where($this->column, '!=', '');

            return;
        }

        if ($operator === self::OPERATOR_IS) {
            (new FiltersExact())($query, is_array($operand) ? $operand : $this->normalize($operand), $this->column);

            return;
        }

        if ($operator === self::OPERATOR_IS_NOT) {
            if (is_array($operand)) {
                $query->whereNotIn($this->column, $operand);

                return;
            }

            $query->where($this->column, '!=', $this->normalize($operand));

            return;
        }

        (new FiltersPartial())($query, $operand, $this->column);
    }

    /**
     * @return array{0: string, 1: mixed}
     */
    private function extractOperatorAndOperand(mixed $value, string $defaultOperator): array
    {
        if (is_array($value) && array_key_exists('operator', $value)) {
            $operator = $this->normalizeToken(Arr::get($value, 'operator'), $defaultOperator);
            $operand = Arr::get($value, 'value');

            if (!in_array($operator, [
                self::OPERATOR_IS,
                self::OPERATOR_IS_NOT,
                self::OPERATOR_CONTAINS,
                self::OPERATOR_GREATER_THAN,
                self::OPERATOR_LESS_THAN,
                self::OPERATOR_HAS_ANY_VALUE,
            ], true)) {
                $operator = $defaultOperator;
            }

            return [$operator, $operand];
        }

        return [$defaultOperator, $value];
    }

    /**
     * @return array{0: string, 1: mixed, 2: mixed}
     */
    private function extractRangeOperatorAndBounds(mixed $value, callable $normalizeBound): array
    {
        if (!is_array($value)) {
            return [self::OPERATOR_IS, null, null];
        }

        $operator = self::OPERATOR_IS;
        $rangeValue = $value;

        if (array_key_exists('operator', $value)) {
            $operator = $this->normalizeToken(Arr::get($value, 'operator'), self::OPERATOR_IS);
            $rangeValue = Arr::get($value, 'value', []);

            if (!in_array($operator, [
                self::OPERATOR_IS,
                self::OPERATOR_IS_NOT,
                self::OPERATOR_GREATER_THAN,
                self::OPERATOR_LESS_THAN,
                self::OPERATOR_HAS_ANY_VALUE,
            ], true)) {
                $operator = self::OPERATOR_IS;
            }
        }

        if (!is_array($rangeValue)) {
            $rangeValue = [];
        }

        $from = $normalizeBound(Arr::get($rangeValue, 'from'));
        $to = $normalizeBound(Arr::get($rangeValue, 'to'));

        return [$operator, $from, $to];
    }

    private function normalize(mixed $value): mixed
    {
        if (is_string($value)) {
            $lower = strtolower($value);
            if ($lower === 'true') {
                return true;
            }

            if ($lower === 'false') {
                return false;
            }
        }

        return $value;
    }

    private function normalizeToken(mixed $value, string $fallback): string
    {
        if ($value === null) {
            return $fallback;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return $fallback;
    }

    private function isBlank(mixed $value): bool
    {
        if (is_array($value) && array_key_exists('operator', $value)) {
            $operator = $this->normalizeToken(Arr::get($value, 'operator'), self::OPERATOR_IS);

            if ($operator === self::OPERATOR_HAS_ANY_VALUE) {
                return false;
            }

            return $this->isBlank(Arr::get($value, 'value'));
        }

        if (is_array($value)) {
            return count(array_filter($value, fn(mixed $item) => !$this->isBlank($item))) === 0;
        }

        return $value === null || $value === '';
    }
}
