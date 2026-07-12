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

        $password = config('admin.password');

        if (! app()->isProduction() || is_string($password)) {
            User::query()->updateOrCreate(
                ['email' => config('admin.email')],
                [
                    'name' => config('admin.name'),
                    'password' => $password ?: 'password',
                    'is_admin' => true,
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
