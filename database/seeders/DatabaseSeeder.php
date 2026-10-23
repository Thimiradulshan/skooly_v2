<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * This stays deliberately small: roles plus one Admin so the app is usable
     * after a fresh migrate. Heavier demo content is opt-in and lives in
     * DemoDataSeeder, which is never run automatically.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $password = app()->environment('testing')
            ? 'password'
            : (string) config('demo.seed_password');

        if (blank($password)) {
            throw new \RuntimeException('Set SKOOLY_DEMO_PASSWORD locally before seeding the demo Admin account.');
        }

        foreach ([Role::SUPERADMIN, Role::ADMIN, Role::ACCOUNTANT, Role::TEACHER] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }

        $adminRole = Role::query()->where('name', Role::ADMIN)->first();

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@skooly.test'],
            ['name' => 'Demo Admin', 'password' => $password, 'email_verified_at' => now(), 'is_active' => true],
        );

        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
