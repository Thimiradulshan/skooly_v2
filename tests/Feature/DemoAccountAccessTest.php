<?php

use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(LazilyRefreshDatabase::class);

it('provisions an active verified login and role home for every demo account', function () {
    $this->seed(DemoDataSeeder::class);

    $accounts = [
        ['superadmin@skooly.test', Role::SUPERADMIN, 'users.index'],
        ['admin@skooly.test', Role::ADMIN, 'admin.dashboard'],
        ['accountant@skooly.test', Role::ACCOUNTANT, 'payments.collect'],
        ['teacher@skooly.test', Role::TEACHER, 'teacher.portal'],
        ['alice@skooly.test', null, 'guardian.portal'],
    ];

    foreach ($accounts as $index => [$email, $role, $homeRoute]) {
        if ($index > 0) {
            $this->post(route('logout'));
        }

        RateLimiter::clear(strtolower($email).'|127.0.0.1');

        $user = User::query()->where('email', $email)->sole();

        expect($user->is_active)->toBeTrue()
            ->and($user->email_verified_at)->not->toBeNull()
            ->and($user->two_factor_confirmed_at)->toBeNull();

        if ($role !== null) {
            expect($user->hasRole($role))->toBeTrue();
        } else {
            expect($user->guardian_id)->not->toBeNull();
        }

        $this->post(route('login.store'), [
            'email' => $email,
            'password' => 'password',
        ])->assertRedirect(route($homeRoute));

        $this->assertAuthenticatedAs($user);
    }
});

it('enforces the Superadmin account-administration boundary', function () {
    $this->seed(DemoDataSeeder::class);
    $superadmin = User::query()->where('email', 'superadmin@skooly.test')->sole();

    $this->actingAs($superadmin)->get(route('users.index'))->assertOk();
    $this->actingAs($superadmin)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($superadmin)->get(route('families.index'))->assertForbidden();
});

it('denies each demo role its unauthorized workspaces', function () {
    $this->seed(DemoDataSeeder::class);

    $accountant = User::query()->where('email', 'accountant@skooly.test')->sole();
    $teacher = User::query()->where('email', 'teacher@skooly.test')->sole();
    $guardian = User::query()->where('email', 'alice@skooly.test')->sole();

    $this->actingAs($accountant)->get(route('payments.collect'))->assertOk();
    $this->actingAs($accountant)->get(route('families.index'))->assertForbidden();
    $this->actingAs($accountant)->get(route('users.index'))->assertForbidden();

    $this->actingAs($teacher)->get(route('teacher.portal'))->assertOk();
    $this->actingAs($teacher)->get(route('families.index'))->assertForbidden();
    $this->actingAs($teacher)->get(route('guardian.portal'))->assertForbidden();

    $this->actingAs($guardian)->get(route('guardian.portal'))->assertOk();
    $this->actingAs($guardian)->get(route('students.index'))->assertForbidden();
});

it('keeps an unlinked student hidden from the demo Guardian', function () {
    $this->seed(DemoDataSeeder::class);

    $guardian = User::query()->where('email', 'alice@skooly.test')->sole();
    $unlinkedStudent = Student::query()->where('admission_no', 'ADM-DEMO-003')->sole();

    $this->actingAs($guardian)
        ->get(route('guardian.students.show', $unlinkedStudent))
        ->assertNotFound();
});
