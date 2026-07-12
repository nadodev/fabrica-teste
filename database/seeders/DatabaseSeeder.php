<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CatalogSeeder::class);

        $password = env('ADMIN_PASSWORD');

        if (! app()->isProduction() || is_string($password)) {
            User::query()->updateOrCreate(
                ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
                [
                    'name' => env('ADMIN_NAME', 'Administrador'),
                    'password' => $password ?: 'password',
                    'is_admin' => true,
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
