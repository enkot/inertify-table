<?php

declare(strict_types=1);

namespace Inertify\Table;

class InertifyTable
{
    public static function make(string $name): Table
    {
        return Table::make($name);
    }
}
