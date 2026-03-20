<?php

declare(strict_types=1);

namespace Inertify\Table;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Inertify\Table\Support\TableState;

class Table
{
    /** @var array<string, Column> */
    private array $columns = [];

    /** @var array<string, Filter> */
    private array $filters = [];

    /** @var array<string, Sort> */
    private array $sorts = [];

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

    public function filter(Filter|string $filter): self
    {
        if (is_string($filter)) {
            $filter = $this->inferFilterFromColumn($filter);
        }

        $this->filters[$filter->key] = $filter;

        return $this;
    }

    /**
     * @param array<int, Filter|string> $filters
     */
    public function allowedFilters(array $filters): self
    {
        $this->filters = [];

        foreach ($filters as $filter) {
            $this->filter($filter);
        }

        return $this;
    }

    public function sort(Sort|string $sort): self
    {
        if (is_string($sort)) {
            $sort = Sort::make($sort);
        }

        $this->sorts[$sort->key] = $sort;

        return $this;
    }

    /**
     * @param array<int, Sort|string> $sorts
     */
    public function allowedSorts(array $sorts): self
    {
        $this->sorts = [];

        foreach ($sorts as $sort) {
            $this->sort($sort);
        }

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

    /**
     * @param EloquentBuilder|BaseBuilder $query
     */
    public function apply(EloquentBuilder|BaseBuilder $query, ?Request $request = null): EloquentBuilder|BaseBuilder
    {
        $state = $this->state($request);

        foreach ($this->filters as $key => $filter) {
            if (!array_key_exists($key, $state->filters)) {
                continue;
            }

            $filter->apply($query, $state->filters[$key]);
        }

        if ($state->sort && isset($this->sorts[$state->sort]) && $state->direction) {
            $this->sorts[$state->sort]->apply($query, $state->direction);
        }

        return $query;
    }

    /**
     * @param EloquentBuilder|BaseBuilder $query
     * @param array<int, string> $columns
     */
    public function paginate(
        EloquentBuilder|BaseBuilder $query,
        ?Request $request = null,
        array $columns = ['*']
    ): LengthAwarePaginator {
        $state = $this->state($request);
        $this->apply($query, $request);

        return $query
            ->paginate($state->perPage, $columns, $this->queryKeys()['page'], $state->page)
            ->withQueryString();
    }

    /**
     * @param EloquentBuilder|BaseBuilder $query
     * @param array<int, string> $columns
     */
    public function payload(
        EloquentBuilder|BaseBuilder $query,
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

        [$sort, $direction] = $this->resolveSort((string) $request->query($keys['sort'], ''));

        if ($sort === null && $this->defaultSort !== null) {
            [$sort, $direction] = $this->resolveSort($this->defaultSort);
        }

        if ($sort !== null && !isset($this->sorts[$sort])) {
            $sort = null;
            $direction = null;
        }

        $rawFilters = $request->query($keys['filters'], []);
        $inputFilters = is_array($rawFilters) ? $rawFilters : [];

        $filters = [];
        foreach ($this->filters as $key => $filter) {
            if (array_key_exists($key, $inputFilters)) {
                $filters[$key] = $inputFilters[$key];
                continue;
            }

            if ($filter->default !== null) {
                $filters[$key] = $filter->default;
            }
        }

        return new TableState(
            page: $page,
            perPage: $perPage,
            sort: $sort,
            direction: $direction,
            filters: $filters
        );
    }

    public function meta(?Request $request = null, ?LengthAwarePaginator $paginator = null): array
    {
        $state = $this->state($request);

        $columns = array_map(function (Column $column): array {
            $columnArray = $column->toArray();
            $columnArray['sortable'] = $column->sortable || isset($this->sorts[$column->key]);
            $columnArray['filterable'] = $column->filterable || isset($this->filters[$column->key]);

            return $columnArray;
        }, array_values($this->columnsForMeta()));

        $sorts = [];
        foreach ($this->sorts as $sort) {
            $sorts[] = $sort->toArray($state->sort === $sort->key ? $state->direction : null);
        }

        $filters = [];
        foreach ($this->filters as $filter) {
            $filters[] = $filter->toArray(Arr::get($state->filters, $filter->key));
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
     * @return array<string, Column>
     */
    private function columnsForMeta(): array
    {
        if ($this->columns !== []) {
            return $this->columns;
        }

        $columns = [];
        foreach (array_keys($this->sorts + $this->filters) as $key) {
            $columns[$key] = Column::make($key);
        }

        return $columns;
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

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveSort(string $sortInput): array
    {
        $sortInput = trim($sortInput);
        if ($sortInput === '') {
            return [null, null];
        }

        if (str_starts_with($sortInput, '-')) {
            $sort = ltrim($sortInput, '-');

            return [$sort !== '' ? $sort : null, $sort !== '' ? 'desc' : null];
        }

        return [$sortInput, 'asc'];
    }

    private function inferFilterFromColumn(string $key): Filter
    {
        $column = $this->columns[$key] ?? null;

        if (!$column) {
            return Filter::partial($key);
        }

        $rawType = $column->meta['type'] ?? null;

        if (!is_string($rawType)) {
            return Filter::partial($key, $column->key, $column->label);
        }

        $type = strtolower(trim($rawType));

        if ($type === '') {
            return Filter::partial($key, $column->key, $column->label);
        }

        return match ($type) {
            'number', 'numeric', 'int', 'integer', 'float', 'double', 'decimal' => Filter::numberRange($key, $column->key, $column->label),
            'date', 'datetime', 'timestamp' => Filter::dateRange($key, $column->key, $column->label),
            'boolean', 'bool' => Filter::exact($key, $column->key, $column->label),
            default => Filter::partial($key, $column->key, $column->label),
        };
    }
}
