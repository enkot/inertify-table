<?php

declare(strict_types=1);

namespace Taras\InertiaHeadlessTable\Tests;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Taras\InertiaHeadlessTable\Column;
use Taras\InertiaHeadlessTable\Filter;
use Taras\InertiaHeadlessTable\InertiaTable;
use Taras\InertiaHeadlessTable\Table;

class TableTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_applies_filtering_sorting_and_pagination(): void
    {
        $table = InertiaTable::make('users')
            ->allowedSorts(['name', 'email'])
            ->allowedFilters([
                Filter::partial('name'),
                Filter::exact('role'),
            ])
            ->defaultPerPage(2)
            ->perPageOptions([1, 2, 5]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'name' => 'a',
                'role' => 'admin',
            ],
            'users_sort' => '-name',
            'users_per_page' => 1,
            'users_page' => 2,
        ]);

        $query = Mockery::mock(EloquentBuilder::class);
        $query->shouldReceive('where')->once()->with('name', 'like', '%a%')->andReturnSelf();
        $query->shouldReceive('where')->once()->with('role', '=', 'admin')->andReturnSelf();
        $query->shouldReceive('orderBy')->once()->with('name', 'desc')->andReturnSelf();
        $query->shouldReceive('paginate')
            ->once()
            ->with(1, ['*'], 'users_page', 2)
            ->andReturn(new LengthAwarePaginator(
                [['name' => 'Aaron']],
                2,
                1,
                2,
                ['path' => '/users', 'pageName' => 'users_page']
            ));

        $paginator = $table->paginate($query, $request);
        $items = $paginator->items();

        $this->assertCount(1, $items);
        $this->assertSame('Aaron', $items[0]['name']);
        $this->assertSame(2, $paginator->currentPage());
        $this->assertSame(1, $paginator->perPage());
        $this->assertSame(2, $paginator->total());
    }

    public function test_it_returns_headless_metadata(): void
    {
        $table = Table::make('users')
            ->columns(['name', 'email', 'role'])
            ->allowedSorts(['name', 'email'])
            ->allowedFilters([
                Filter::partial('name'),
                Filter::exact('role'),
            ])
            ->defaultSort('-email')
            ->defaultPerPage(15)
            ->perPageOptions([15, 30, 50]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'role' => 'editor',
            ],
            'users_sort' => 'name',
        ]);

        $paginator = new LengthAwarePaginator(
            [['name' => 'Nina']],
            4,
            15,
            1,
            ['path' => '/users', 'pageName' => 'users_page']
        );
        $meta = $table->meta($request, $paginator);

        $this->assertSame('users', $meta['name']);
        $this->assertSame('users_sort', $meta['queryKeys']['sort']);
        $this->assertSame('name', $meta['state']['sort']);
        $this->assertSame('asc', $meta['state']['direction']);
        $this->assertSame('editor', $meta['state']['filters']['role']);
        $this->assertSame(4, $meta['pagination']['total']);
        $this->assertCount(3, $meta['columns']);
        $this->assertCount(2, $meta['sorts']);
        $this->assertCount(2, $meta['filters']);
    }

    public function test_it_returns_payload_ready_for_inertia_props(): void
    {
        $table = Table::make('users')
            ->allowedSorts(['name'])
            ->allowedFilters([Filter::partial('name')])
            ->defaultPerPage(10)
            ->perPageOptions([10, 20]);

        $query = Mockery::mock(EloquentBuilder::class);
        $query->shouldReceive('paginate')
            ->once()
            ->with(10, ['*'], 'users_page', 1)
            ->andReturn(new LengthAwarePaginator(
                [['name' => 'A'], ['name' => 'B']],
                5,
                10,
                1,
                ['path' => '/users', 'pageName' => 'users_page']
            ));

        $payload = $table->payload($query, Request::create('/users', 'GET'));

        $this->assertArrayHasKey('rows', $payload);
        $this->assertArrayHasKey('table', $payload);
        $this->assertSame('users', $payload['table']['name']);
        $this->assertSame(5, $payload['rows']->total());
    }

    public function test_it_applies_number_range_filter_with_both_bounds(): void
    {
        $table = Table::make('users')
            ->allowedFilters([
                Filter::numberRange('price'),
            ]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'price' => [
                    'from' => '10',
                    'to' => '20',
                ],
            ],
        ]);

        $query = Mockery::mock(EloquentBuilder::class);
        $query->shouldReceive('whereBetween')->once()->with('price', ['10', '20'])->andReturnSelf();

        $table->apply($query, $request);
        $this->addToAssertionCount(1);
    }

    public function test_it_applies_number_range_filter_with_single_bound(): void
    {
        $table = Table::make('users')
            ->allowedFilters([
                Filter::numberRange('price'),
            ]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'price' => [
                    'from' => '100',
                ],
            ],
        ]);

        $query = Mockery::mock(EloquentBuilder::class);
        $query->shouldReceive('where')->once()->with('price', '>=', '100')->andReturnSelf();

        $table->apply($query, $request);
        $this->addToAssertionCount(1);
    }

    public function test_it_applies_date_range_filter_with_both_bounds(): void
    {
        $table = Table::make('users')
            ->allowedFilters([
                Filter::dateRange('created_at'),
            ]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'created_at' => [
                    'from' => '2026-01-01',
                    'to' => '2026-01-31',
                ],
            ],
        ]);

        $query = Mockery::mock(EloquentBuilder::class);
        $query->shouldReceive('whereBetween')->once()->with('created_at', ['2026-01-01', '2026-01-31'])->andReturnSelf();

        $table->apply($query, $request);
        $this->addToAssertionCount(1);
    }

    public function test_it_ignores_invalid_number_range_payload(): void
    {
        $table = Table::make('users')
            ->allowedFilters([
                Filter::numberRange('price'),
            ]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'price' => [
                    'from' => 'abc',
                    'to' => '',
                ],
            ],
        ]);

        $query = Mockery::mock(EloquentBuilder::class);

        $table->apply($query, $request);
        $this->assertTrue(true);
    }

    public function test_it_returns_range_filter_metadata_and_value(): void
    {
        $table = Table::make('users')
            ->allowedFilters([
                Filter::numberRange('price', min: 0, max: 1000, step: 1),
                Filter::dateRange('created_at'),
            ]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'price' => [
                    'from' => '5',
                    'to' => '25',
                ],
            ],
        ]);

        $meta = $table->meta($request);

        $this->assertSame('number-range', $meta['filters'][0]['input']);
        $this->assertSame(0, $meta['filters'][0]['rangeMin']);
        $this->assertSame(1000, $meta['filters'][0]['rangeMax']);
        $this->assertSame(1, $meta['filters'][0]['rangeStep']);
        $this->assertSame(['from' => '5', 'to' => '25'], $meta['filters'][0]['value']);
        $this->assertSame('date-range', $meta['filters'][1]['input']);
    }

    public function test_partial_filter_with_is_operator_uses_exact_match(): void
    {
        $table = Table::make('users')
            ->allowedFilters([
                Filter::partial('name'),
            ]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'name' => [
                    'operator' => 'is',
                    'value' => 'Samantha',
                ],
            ],
        ]);

        $query = Mockery::mock(EloquentBuilder::class);
        $query->shouldReceive('where')->once()->with('name', '=', 'Samantha')->andReturnSelf();

        $table->apply($query, $request);
        $this->addToAssertionCount(1);
    }

    public function test_partial_filter_with_contains_operator_uses_like_match(): void
    {
        $table = Table::make('users')
            ->allowedFilters([
                Filter::partial('name'),
            ]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'name' => [
                    'operator' => 'contains',
                    'value' => 'Samantha',
                ],
            ],
        ]);

        $query = Mockery::mock(EloquentBuilder::class);
        $query->shouldReceive('where')->once()->with('name', 'like', '%Samantha%')->andReturnSelf();

        $table->apply($query, $request);
        $this->addToAssertionCount(1);
    }

    public function test_partial_filter_with_contains_operator_ignores_non_scalar_operand(): void
    {
        $table = Table::make('users')
            ->allowedFilters([
                Filter::partial('name'),
            ]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'name' => [
                    'operator' => 'contains',
                    'value' => [
                        'unexpected' => 'shape',
                    ],
                ],
            ],
        ]);

        $query = Mockery::mock(EloquentBuilder::class);

        $table->apply($query, $request);
        $this->assertTrue(true);
    }

    public function test_partial_filter_with_legacy_grouped_payload_uses_first_rule(): void
    {
        $table = Table::make('users')
            ->allowedFilters([
                Filter::partial('email'),
            ]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'email' => [
                    'mode' => 'all',
                    'rules' => [
                        [
                            'operator' => 'contains',
                        ],
                        [
                            'value' => 'nedra',
                        ],
                    ],
                ],
            ],
        ]);

        $query = Mockery::mock(EloquentBuilder::class);
        $query->shouldReceive('where')->once()->with('email', 'like', '%nedra%')->andReturnSelf();

        $table->apply($query, $request);
        $this->addToAssertionCount(1);
    }


    public function test_number_range_filter_with_is_not_operator_uses_not_between(): void
    {
        $table = Table::make('users')
            ->allowedFilters([
                Filter::numberRange('price'),
            ]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'price' => [
                    'operator' => 'is_not',
                    'value' => [
                        'from' => '10',
                        'to' => '20',
                    ],
                ],
            ],
        ]);

        $query = Mockery::mock(EloquentBuilder::class);
        $query->shouldReceive('whereNotBetween')->once()->with('price', ['10', '20'])->andReturnSelf();

        $table->apply($query, $request);
        $this->addToAssertionCount(1);
    }

    public function test_number_range_filter_with_greater_than_operator_uses_strict_gt(): void
    {
        $table = Table::make('users')
            ->allowedFilters([
                Filter::numberRange('price'),
            ]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'price' => [
                    'operator' => 'greater_than',
                    'value' => [
                        'from' => '100',
                    ],
                ],
            ],
        ]);

        $query = Mockery::mock(EloquentBuilder::class);
        $query->shouldReceive('where')->once()->with('price', '>', '100')->andReturnSelf();

        $table->apply($query, $request);
        $this->addToAssertionCount(1);
    }

    public function test_number_range_filter_with_less_than_operator_uses_strict_lt(): void
    {
        $table = Table::make('users')
            ->allowedFilters([
                Filter::numberRange('price'),
            ]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'price' => [
                    'operator' => 'less_than',
                    'value' => [
                        'to' => '50',
                    ],
                ],
            ],
        ]);

        $query = Mockery::mock(EloquentBuilder::class);
        $query->shouldReceive('where')->once()->with('price', '<', '50')->andReturnSelf();

        $table->apply($query, $request);
        $this->addToAssertionCount(1);
    }

    public function test_date_range_filter_with_greater_than_operator_uses_strict_gt(): void
    {
        $table = Table::make('users')
            ->allowedFilters([
                Filter::dateRange('created_at'),
            ]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'created_at' => [
                    'operator' => 'greater_than',
                    'value' => [
                        'from' => '2026-01-10',
                    ],
                ],
            ],
        ]);

        $query = Mockery::mock(EloquentBuilder::class);
        $query->shouldReceive('where')->once()->with('created_at', '>', '2026-01-10')->andReturnSelf();

        $table->apply($query, $request);
        $this->addToAssertionCount(1);
    }

    public function test_date_range_filter_with_less_than_operator_uses_strict_lt(): void
    {
        $table = Table::make('users')
            ->allowedFilters([
                Filter::dateRange('created_at'),
            ]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'created_at' => [
                    'operator' => 'less_than',
                    'value' => [
                        'to' => '2026-01-31',
                    ],
                ],
            ],
        ]);

        $query = Mockery::mock(EloquentBuilder::class);
        $query->shouldReceive('where')->once()->with('created_at', '<', '2026-01-31')->andReturnSelf();

        $table->apply($query, $request);
        $this->addToAssertionCount(1);
    }

    public function test_string_allowed_filters_infer_filter_type_from_column_type(): void
    {
        $table = Table::make('users')
            ->columns([
                Column::make('price', 'Price')->type('number'),
                Column::make('created_at', 'Created')->type('date'),
                Column::make('name', 'Name'),
            ])
            ->allowedFilters(['price', 'created_at', 'name']);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'price' => [
                    'from' => '10',
                    'to' => '20',
                ],
                'created_at' => [
                    'from' => '2026-01-01',
                    'to' => '2026-01-31',
                ],
                'name' => 'sam',
            ],
        ]);

        $query = Mockery::mock(EloquentBuilder::class);
        $query->shouldReceive('whereBetween')->once()->with('price', ['10', '20'])->andReturnSelf();
        $query->shouldReceive('whereBetween')->once()->with('created_at', ['2026-01-01', '2026-01-31'])->andReturnSelf();
        $query->shouldReceive('where')->once()->with('name', 'like', '%sam%')->andReturnSelf();

        $table->apply($query, $request);

        $meta = $table->meta($request);
        $inputsByKey = [];
        foreach ($meta['filters'] as $filter) {
            $inputsByKey[$filter['key']] = $filter['input'];
        }

        $this->assertSame('number-range', $inputsByKey['price']);
        $this->assertSame('date-range', $inputsByKey['created_at']);
        $this->assertSame('text', $inputsByKey['name']);
    }

    public function test_explicit_filter_definition_overrides_column_type_inference(): void
    {
        $table = Table::make('users')
            ->columns([
                Column::make('price', 'Price')->type('number'),
            ])
            ->allowedFilters([
                Filter::exact('price', 'price', 'Price'),
            ]);

        $request = Request::create('/users', 'GET', [
            'users_filters' => [
                'price' => '100',
            ],
        ]);

        $query = Mockery::mock(EloquentBuilder::class);
        $query->shouldReceive('where')->once()->with('price', '=', '100')->andReturnSelf();

        $table->apply($query, $request);

        $meta = $table->meta($request);
        $this->assertSame('text', $meta['filters'][0]['input']);
    }
}
