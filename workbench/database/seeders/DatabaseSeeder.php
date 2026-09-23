<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * A single user so the panels can be exercised with `composer serve`
     * (password is "password").
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'okta@laravel.com'],
            [
                'name' => 'Filament Okta',
                'password' => 'password',
            ],
        );
    }
}
