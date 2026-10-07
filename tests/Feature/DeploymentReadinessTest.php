<?php

use App\Models\Family;
use App\Models\PaymentReminder;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentDueItem;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

it('keeps the base seeder minimal and free of demo content', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Role::query()->count())->toBe(4);
    expect(User::query()->where('email', 'admin@skooly.test')->count())->toBe(1);
    expect(Family::count())->toBe(0);
    expect(Student::count())->toBe(0);
});

it('does not run the demo seeder from the base seeder', function () {
    $this->seed(DatabaseSeeder::class);

    // Demo content must only appear when the demo seeder is requested explicitly.
    expect(Family::count())->toBe(0);
    expect(Student::count())->toBe(0);
    expect(StudentDueItem::count())->toBe(0);
    expect(PaymentReminder::count())->toBe(0);
});

it('keeps the base seeder inert in production', function () {
    app()['env'] = 'production';
    $this->withoutMockingConsoleOutput();

    try {
        $this->seed(DatabaseSeeder::class);

        expect(User::count())->toBe(0);
        expect(Role::count())->toBe(0);
    } finally {
        app()['env'] = 'testing';
    }
});

it('does not seed demo data in production', function () {
    app()['env'] = 'production';
    $this->withoutMockingConsoleOutput();

    try {
        $this->seed(DemoDataSeeder::class);

        expect(Family::count())->toBe(0);
        expect(Student::count())->toBe(0);
    } finally {
        app()['env'] = 'testing';
    }
});

it('requires an explicit APP_KEY and does not ship one', function () {
    $example = (string) file_get_contents(base_path('.env.example'));

    expect($example)->toContain('APP_KEY=');
    expect($example)->toMatch('/^APP_KEY=\s*$/m');
});

it('ships an env example with the required production keys and no real secrets', function () {
    $example = (string) file_get_contents(base_path('.env.example'));

    foreach ([
        'APP_NAME', 'APP_ENV', 'APP_KEY', 'APP_DEBUG', 'APP_URL',
        'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
        'SESSION_DRIVER', 'CACHE_STORE', 'QUEUE_CONNECTION', 'LOG_CHANNEL', 'LOG_LEVEL',
    ] as $key) {
        expect($example)->toContain($key.'=');
    }

    expect($example)->toMatch('/^APP_KEY=\s*$/m');
    expect($example)->toMatch('/^DB_PASSWORD=\s*$/m');
    expect($example)->toMatch('/^APP_DEBUG=true$/m');
});

it('ignores the real env file so secrets cannot be committed', function () {
    $gitignore = (string) file_get_contents(base_path('.gitignore'));

    expect($gitignore)->toContain('.env');
    expect($gitignore)->not->toMatch('/^\s*!\.env\s*$/m');
});

it('keeps session cookies configurable for https deployments', function () {
    expect(config('session.http_only'))->toBeTrue();
    expect(config('session.same_site'))->toBe('lax');
    expect(config('session.secure'))->toBeNull();

    // SESSION_SECURE_COOKIE is driven purely by the environment, so an https
    // deployment can force secure cookies without a code change.
    expect((string) file_get_contents(config_path('session.php')))
        ->toContain("env('SESSION_SECURE_COOKIE')");
});

it('keeps dangerous routes absent for deployment', function () {
    $dangerous = [
        'payments.edit', 'payments.update', 'payments.destroy', 'payments.refund',
        'receipts.destroy', 'receipts.export',
        'promotion-batches.destroy', 'promotion-batches.reverse',
        'families.destroy', 'events.destroy',
        'payment-reminders.send', 'payment-reminders.edit', 'payment-reminders.update', 'payment-reminders.destroy',
    ];

    foreach ($dangerous as $name) {
        expect(Route::has($name))->toBeFalse();
    }

    $apiRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'api/'));

    expect($apiRoutes)->toBeEmpty();
});

it('protects every admin route with auth and the role middleware', function () {
    $adminRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'admin')
            || str_starts_with($route->uri(), 'families')
            || str_starts_with($route->uri(), 'payments')
            || str_starts_with($route->uri(), 'receipts')
            || str_starts_with($route->uri(), 'promotion-batches')
            || str_starts_with($route->uri(), 'payment-reminders')
            || str_starts_with($route->uri(), 'fee-')
            || str_starts_with($route->uri(), 'due-')
            || str_starts_with($route->uri(), 'dues-')
            || str_starts_with($route->uri(), 'events')
            || str_starts_with($route->uri(), 'students/'));

    expect($adminRoutes)->not->toBeEmpty();

    foreach ($adminRoutes as $route) {
        $middleware = $route->gatherMiddleware();

        expect($middleware)->toContain('auth');
        expect(implode(',', $middleware))->toContain('role:Admin');
    }
});
