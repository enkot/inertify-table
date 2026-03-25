<?php

declare(strict_types=1);

namespace Inertify\Table;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Inertia\ResponseFactory;

class TableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/inertify-table.php',
            'inertify-table'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/inertify-table.php' => config_path('inertify-table.php'),
        ], 'inertify-table-config');

        ResponseFactory::macro('tablePayload', function (
            string $name,
            mixed $query,
            callable $configure,
            string $rowsKey = 'rows',
            string $metaKey = 'meta',
            ?Request $request = null,
            array $columns = ['*']
        ): array {
            $table = Table::make($name);
            $configure($table);

            return $table->payload($query, $request, $columns, $rowsKey, $metaKey);
        });
    }
}
