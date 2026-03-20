<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->delete();

        $roles = ['admin', 'editor', 'viewer'];

        for ($index = 1; $index <= 75; $index++) {
            $name = "User {$index}";

            User::query()->create([
                'name' => $name,
                'email' => Str::slug($name) . '@example.com',
                'role' => $roles[$index % count($roles)],
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }
    }
}
