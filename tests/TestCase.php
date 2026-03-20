<?php

declare(strict_types=1);

namespace Inertify\Table\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Inertify\Table\HeadlessTableServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            HeadlessTableServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:12345678901234567890123456789012');
    }
}
