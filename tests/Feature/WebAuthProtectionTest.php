<?php

use App\Models\AuditLog;
use App\Models\Family;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

it('lets a guest view the login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Sign in');
});

it('lets a valid user log in', function () {
    $admin = adminUser();

    $this->post(route('login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('families.index'));

    $this->assertAuthenticatedAs($admin);
});

it('rejects invalid login credentials', function () {
    $admin = adminUser();

    $this->post(route('login.store'), [
        'email' => $admin->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('validates that email and password are required', function () {
    $this->post(route('login.store'), [])->assertSessionHasErrors(['email', 'password']);
});

it('lets an authenticated user log out', function () {
    $this->actingAs(adminUser());

    $this->post(route('logout'))->assertRedirect(route('login'));

    $this->assertGuest();
});

it('redirects a guest to login from the families index', function () {
    $this->get(route('families.index'))->assertRedirect(route('login'));
});

it('prevents a guest from posting a family', function () {
    $this->post(route('families.store'), ['family_code' => 'FAM-GUEST'])->assertRedirect(route('login'));

    expect(Family::count())->toBe(0);
    expect(AuditLog::count())->toBe(0);
});

it('lets an admin view the families index', function () {
    Family::factory()->create(['family_code' => 'FAM-ADMIN']);

    $this->actingAs(adminUser())
        ->get(route('families.index'))
        ->assertOk()
        ->assertSee('FAM-ADMIN');
});

it('lets an admin open the family create page', function () {
    $this->actingAs(adminUser())->get(route('families.create'))->assertOk();
});

it('lets an admin create a family through the protected route', function () {
    $this->actingAs(adminUser())
        ->post(route('families.store'), ['family_code' => 'FAM-PROTECTED'])
        ->assertRedirect();

    expect(Family::query()->sole()->family_code)->toBe('FAM-PROTECTED');
    expect(AuditLog::where('action', AuditLog::ACTION_FAMILY_CREATED)->count())->toBe(1);
});

it('denies a teacher access to the families index', function () {
    $this->actingAs(userWithRole(Role::TEACHER))
        ->get(route('families.index'))
        ->assertForbidden();
});

it('denies an accountant access to the families index', function () {
    $this->actingAs(userWithRole(Role::ACCOUNTANT))
        ->get(route('families.index'))
        ->assertForbidden();
});

it('denies a non-admin user every protected registration route', function () {
    $family = Family::factory()->create();
    $teacher = userWithRole(Role::TEACHER);

    $this->actingAs($teacher)->get(route('families.create'))->assertForbidden();
    $this->actingAs($teacher)->get(route('families.show', $family))->assertForbidden();
    $this->actingAs($teacher)->get(route('families.edit', $family))->assertForbidden();
    $this->actingAs($teacher)->post(route('families.store'), ['family_code' => 'FAM-DENIED'])->assertForbidden();
    $this->actingAs($teacher)->get(route('families.students.create', $family))->assertForbidden();
    $this->actingAs($teacher)->post(route('families.students.store', $family), [])->assertForbidden();

    expect(Family::count())->toBe(1);
});

it('denies a user with no role at all', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('families.index'))
        ->assertForbidden();
});

it('redirects to the intended page after a successful login', function () {
    $admin = adminUser();

    $this->get(route('families.create'))->assertRedirect(route('login'));

    $this->post(route('login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('families.create'));
});

it('adds no API routes or API authentication', function () {
    $apiRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'api/'))
        ->count();

    expect($apiRoutes)->toBe(0);
    expect(Route::has('login'))->toBeTrue();
    expect(Route::has('login.store'))->toBeTrue();
    expect(Route::has('logout'))->toBeTrue();
    expect(config('sanctum'))->toBeNull();
});

it('keeps the root route public', function () {
    $this->get('/')->assertOk();
});
