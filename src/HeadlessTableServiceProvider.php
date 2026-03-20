<?php

declare(strict_types=1);

namespace Taras\InertiaHeadlessTable;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Inertia\ResponseFactory;

class HeadlessTableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/inertia-headless-table.php',
            'inertia-headless-table'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/inertia-headless-table.php' => config_path('inertia-headless-table.php'),
        ], 'inertia-headless-table-config');

        ResponseFactory::macro('tablePayload', function (
            string $name,
            mixed $query,
            callable $configure,
            string $rowsKey = 'rows',
            string $metaKey = 'table',
            ?Request $request = null,
            array $columns = ['*']
        ): array {
            $table = Table::make($name);
            $configure($table);

            return $table->payload($query, $request, $columns, $rowsKey, $metaKey);
        });
    }
}
