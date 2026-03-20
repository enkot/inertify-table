<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Inertify\Table\Column;
use Inertify\Table\InertifyTable;

class UserIndexController extends Controller
{
    public function __invoke(): Response
    {
        $table = InertifyTable::make('users')
            ->columns([
                Column::make('id', 'ID')->type('number')->filterable(),
                Column::make('name', 'Name')->sortable()->filterable(),
                Column::make('email', 'Email')->sortable()->filterable(),
                Column::make('role', 'Role')->sortable()->filterable(),
                Column::make('created_at', 'Created')->type('date')->sortable()->filterable(),
            ])
            ->allowedSorts(['name', 'email', 'role', 'created_at'])
            ->allowedFilters(['id', 'name', 'email', 'role', 'created_at'])
            ->defaultSort('-created_at')
            ->defaultPerPage(10)
            ->perPageOptions([10, 25, 50]);

        $payload = $table->payload(
            query: User::query()->select([
                'id',
                'name',
                'email',
                'role',
                'created_at',
            ]),
            rowsKey: 'rows',
            metaKey: 'meta'
        );

        return Inertia::render('users/Index', [
            'table' => $payload,
        ]);
    }
}
