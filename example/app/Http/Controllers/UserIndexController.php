<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Inertify\Table\Column;
use Inertify\Table\Table;

class UserIndexController extends Controller
{
    public function __invoke(): Response
    {
        $table = Table::make('users')
            ->columns([
                Column::make('id', 'ID')->type('number'),
                Column::make('name', 'Name'),
                Column::make('email', 'Email'),
                Column::make('created_at', 'Created')->type('date'),
            ])
            ->sorts(['id', 'name', 'email', 'created_at'])
            ->filters(['name', 'email', 'created_at'])
            ->defaultSort('-created_at');

        return Inertia::render('users/Index', [
            'table' => $table->payload(User::query()),
        ]);
    }
}
