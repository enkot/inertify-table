<?php

declare(strict_types=1);

namespace Inertify\Table\Tests;

use Illuminate\Http\Request;
use Inertify\Table\Column;
use Inertify\Table\Table;
use Spatie\QueryBuilder\AllowedFilter;
use Mockery;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\LengthAwarePaginator;

class TableTest extends TestCase
{
    public function test_it_returns_headless_metadata(): void
    {
        $request = Request::create('/users', 'GET', [
            'users_filters' => ['role' => 'admin'],
            'users_sort' => '-name',
        ]);

        $table = Table::make('users')
            ->columns([
                Column::make('name', 'Name')->sortable(),
                Column::make('role', 'Role')->filterable(),
            ])
            ->searchInput('name', 'Name')
            ->selectFilter('role', ['admin' => 'Admin', 'user' => 'User'])
            ->allowedFilters([
                AllowedFilter::exact('role'),
                'name',
            ])
            ->allowedSorts(['name', 'role']);

        $meta = $table->meta($request);

        $this->assertEquals('users', $meta['name']);
        
        $this->assertArrayHasKey('queryKeys', $meta);
        $this->assertEquals('users_filters', $meta['queryKeys']['filters']);
        
        $this->assertArrayHasKey('state', $meta);
        $this->assertEquals('admin', $meta['state']['filters']['role']);
        $this->assertEquals('name', $meta['state']['sort']);
        $this->assertEquals('desc', $meta['state']['direction']);

        $this->assertArrayHasKey('columns', $meta);
        $this->assertCount(2, $meta['columns']);
        
        $this->assertArrayHasKey('filters', $meta);
        $this->assertCount(2, $meta['filters']);
        $this->assertEquals('admin', $meta['filters'][1]['value']);
        $this->assertEquals('select', $meta['filters'][1]['input']);

        $this->assertArrayHasKey('sorts', $meta);
        $this->assertCount(2, $meta['sorts']);
        $this->assertEquals('name', $meta['sorts'][0]['key']);
        $this->assertEquals('desc', $meta['sorts'][0]['direction']);
    }

    public function test_it_formats_options_correctly(): void
    {
        $table = Table::make('users')->selectFilter('role', ['admin', 'user']);
        
        $meta = $table->meta();
        $options = $meta['filters'][0]['options'];
        
        $this->assertEquals('admin', $options[0]['value']);
        $this->assertEquals('Admin', $options[0]['label']);
        
        $this->assertEquals('user', $options[1]['value']);
        $this->assertEquals('User', $options[1]['label']);
    }
}
