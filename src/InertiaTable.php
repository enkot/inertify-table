<?php

declare(strict_types=1);

namespace Taras\InertiaHeadlessTable;

class InertiaTable
{
    public static function make(string $name): Table
    {
        return Table::make($name);
    }
}
