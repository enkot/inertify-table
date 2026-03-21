<?php

declare(strict_types=1);

namespace Inertify\Table;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\QueryBuilderRequest;
use Inertify\Table\Support\TableState;

class Table
{
    /** @var array<string, Column> */
    private array $columns = [];

    /** @var array<string, array> */
    private array $uiFilters = [];

    /** @var array<int, AllowedFilter|string> */
    private array $spatieFilters = [];

    /** @var array<int, AllowedSort|string> */
    private array $spatieSorts = [];

    private ?string $defaultSort = null;

    private int $defaultPerPage;

    /** @var int[] */
    private array $perPageOptions;

    private function __construct(private readonly string $name)
    {
        $this->defaultPerPage = (int) config('inertify-table.default_per_page', 15);
        $this->perPageOptions = array_values(array_map(
            static fn(mixed $value) => (int) $value,
            config('inertify-table.per_page_options', [15, 30, 50])
        ));
    }

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function column(Column|string $column, ?string $label = null): self
    {
        if (is_string($column)) {
            $column = Column::make($column, $label);
        }

        $this->columns[$column->key] = $column;

        return $this;
    }

    /**
     * @param array<int, Column|string> $columns
     */
    public function columns(array $columns): self
    {
        foreach ($columns as $column) {
            $this->column($column);
        }

        return $this;
    }

    public function searchInput(string $key, ?string $label = null, mixed $defaultValue = null): self
    {
        $this->uiFilters[$key] = [
            'key' => $key,
            'label' => $label ?? Str::headline(str_replace('.', ' ', $key)),
            'column' => $key,
            'input' => 'text',
            'multiple' => false,
            'options' => [],
            'default' => $defaultValue,
        ];

        return $this;
    }

    public function selectFilter(string $key, array $options, ?string $label = null, mixed $defaultValue = null, bool $multiple = false): self
    {
        $this->uiFilters[$key] = [
            'key' => $key,
            'label' => $label ?? Str::headline(str_replace('.', ' ', $key)),
            'column' => $key,
            'input' => 'select',
            'multiple' => $multiple,
            'options' => $this->formatOptions($options),
            'default' => $defaultValue,
        ];

        return $this;
    }

    public function dateRangeFilter(string $key, ?string $label = null, mixed $defaultValue = null): self
    {
        $this->uiFilters[$key] = [
            'key' => $key,
            'label' => $label ?? Str::headline(str_replace('.', ' ', $key)),
            'column' => $key,
            'input' => 'date-range',
            'multiple' => false,
            'options' => [],
            'default' => $defaultValue,
        ];

        return $this;
    }

    public function numberRangeFilter(string $key, ?string $label = null, mixed $defaultValue = null, $min = null, $max = null, $step = null): self
    {
        $this->uiFilters[$key] = [
            'key' => $key,
            'label' => $label ?? Str::headline(str_replace('.', ' ', $key)),
            'column' => $key,
            'input' => 'number-range',
            'multiple' => false,
            'options' => [],
            'default' => $defaultValue,
            'rangeMin' => $min,
            'rangeMax' => $max,
            'rangeStep' => $step,
        ];

        return $this;
    }

    /**
     * @param array<int, AllowedFilter|string> $filters
     */
    public function allowedFilters(array $filters): self
    {
        $this->spatieFilters = $filters;

        return $this;
    }

    /**
     * @param array<int, AllowedSort|string> $sorts
     */
    public function allowedSorts(array $sorts): self
    {
        $this->spatieSorts = $sorts;

        return $this;
    }

    public function defaultSort(string $sort): self
    {
        $this->defaultSort = trim($sort) !== '' ? $sort : null;

        return $this;
    }

    public function defaultPerPage(int $perPage): self
    {
        if ($perPage > 0) {
            $this->defaultPerPage = $perPage;
        }

        return $this;
    }

    /**
     * @param array<int, int> $options
     */
    public function perPageOptions(array $options): self
    {
        $normalized = array_values(array_filter(
            array_map(static fn(mixed $value) => (int) $value, $options),
            static fn(int $value) => $value > 0
        ));

        if ($normalized !== []) {
            $this->perPageOptions = $normalized;
        }

        return $this;
    }

    private function makeSpatieRequest(?Request $request): QueryBuilderRequest
    {
        $request = $request ?? request();
        $keys = $this->queryKeys();

        // Create a fake request for Spatie so we can support prefixed keys mapped to Spatie's global keys
        $spatieRequest = Request::create($request->fullUrl());
        $spatieRequest->query->set('filter', $request->query($keys['filters'], []));
        $spatieRequest->query->set('sort', $request->query($keys['sort'], ''));

        return QueryBuilderRequest::fromRequest($spatieRequest);
    }

    /**
     * @param EloquentBuilder|BaseBuilder|Relation|QueryBuilder|string $query
     */
    public function apply($query, ?Request $request = null): QueryBuilder
    {
        if ($query instanceof QueryBuilder) {
            return $query;
        }

        $spatieRequest = $this->makeSpatieRequest($request);

        $builder = QueryBuilder::for($query, $spatieRequest)
            ->allowedFilters(...$this->spatieFilters)
            ->allowedSorts(...$this->spatieSorts);

        if ($this->defaultSort !== null) {
            $builder->defaultSort($this->defaultSort);
        }

        return $builder;
    }

    /**
     * @param EloquentBuilder|BaseBuilder|Relation|QueryBuilder|string $query
     * @param array<int, string> $columns
     */
    public function paginate(
        $query,
        ?Request $request = null,
        array $columns = ['*']
    ): LengthAwarePaginator {
        $builder = $this->apply($query, $request);
        $request = $request ?? request();
        $keys = $this->queryKeys();

        $page = max((int) $request->query($keys['page'], 1), 1);
        $perPage = max((int) $request->query($keys['perPage'], $this->defaultPerPage), 1);

        if ($this->perPageOptions !== [] && !in_array($perPage, $this->perPageOptions, true)) {
            $perPage = $this->defaultPerPage;
        }

        return $builder
            ->paginate($perPage, $columns, $keys['page'], $page)
            ->withQueryString();
    }

    /**
     * @param EloquentBuilder|BaseBuilder|Relation|QueryBuilder|string $query
     * @param array<int, string> $columns
     */
    public function payload(
        $query,
        ?Request $request = null,
        array $columns = ['*'],
        string $rowsKey = 'rows',
        string $metaKey = 'table'
    ): array {
        $paginator = $this->paginate($query, $request, $columns);

        return [
            $rowsKey => $paginator,
            $metaKey => $this->meta($request, $paginator),
        ];
    }

    public function state(?Request $request = null): TableState
    {
        $request = $request ?? request();
        $keys = $this->queryKeys();

        $page = max((int) $request->query($keys['page'], 1), 1);
        $perPage = max((int) $request->query($keys['perPage'], $this->defaultPerPage), 1);

        if ($this->perPageOptions !== [] && !in_array($perPage, $this->perPageOptions, true)) {
            $perPage = $this->defaultPerPage;
        }

        $sort = (string) $request->query($keys['sort'], '');
        if ($sort === '' && $this->defaultSort !== null) {
            $sort = $this->defaultSort;
        }

        if ($sort !== '') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $sortKey = ltrim($sort, '-');
        } else {
            $sortKey = null;
            $direction = null;
        }

        $rawFilters = $request->query($keys['filters'], []);
        $inputFilters = is_array($rawFilters) ? $rawFilters : [];

        $filters = [];
        foreach ($this->uiFilters as $key => $filter) {
            if (array_key_exists($key, $inputFilters)) {
                $filters[$key] = $inputFilters[$key];
                continue;
            }

            if ($filter['default'] !== null) {
                $filters[$key] = $filter['default'];
            }
        }

        return new TableState(
            page: $page,
            perPage: $perPage,
            sort: $sortKey,
            direction: $direction,
            filters: $filters
        );
    }

    public function meta(?Request $request = null, ?LengthAwarePaginator $paginator = null): array
    {
        $state = $this->state($request);

        $columns = array_map(function (Column $column): array {
            $columnArray = $column->toArray();
            return $columnArray;
        }, array_values($this->columns));

        $sorts = [];
        foreach ($this->spatieSorts as $spatieSort) {
            $sortName = $spatieSort instanceof AllowedSort ? $spatieSort->getName() : $spatieSort;

            // Format for frontend
            $sorts[] = [
                'key' => $sortName,
                'label' => Str::headline(str_replace('.', ' ', $sortName)),
                'column' => $sortName,
                'direction' => $state->sort === $sortName ? $state->direction : null,
            ];
        }

        $filters = [];
        foreach ($this->uiFilters as $key => $filter) {
            $filter['value'] = Arr::get($state->filters, $key);
            $filters[] = $filter;
        }

        return [
            'name' => $this->name,
            'queryKeys' => $this->queryKeys(),
            'state' => $state->toArray(),
            'defaultSort' => $this->defaultSort,
            'defaultPerPage' => $this->defaultPerPage,
            'perPageOptions' => $this->perPageOptions,
            'columns' => $columns,
            'sorts' => $sorts,
            'filters' => $filters,
            'pagination' => $paginator ? [
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'lastPage' => $paginator->lastPage(),
                'hasMorePages' => $paginator->hasMorePages(),
            ] : null,
        ];
    }

    /**
     * @return array{page: string, perPage: string, sort: string, filters: string}
     */
    public function queryKeys(): array
    {
        return [
            'page' => sprintf((string) config('inertify-table.query_keys.page', '%s_page'), $this->name),
            'perPage' => sprintf((string) config('inertify-table.query_keys.per_page', '%s_per_page'), $this->name),
            'sort' => sprintf((string) config('inertify-table.query_keys.sort', '%s_sort'), $this->name),
            'filters' => sprintf((string) config('inertify-table.query_keys.filters', '%s_filters'), $this->name),
        ];
    }

    private function formatOptions(array $options): array
    {
        $formatted = [];
        foreach ($options as $key => $value) {
            if (is_array($value) && isset($value['value'], $value['label'])) {
                $formatted[] = $value;
                continue;
            }

            // Indexed arrays defaults to value=label
            if (is_int($key)) {
                $formatted[] = ['value' => $value, 'label' => Str::headline((string)$value)];
            } else {
                $formatted[] = ['value' => $key, 'label' => $value];
            }
        }

        return $formatted;
    }
}
