<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function superadminUser(): User
{
    $user = User::factory()->create();
    $superadminRole = Role::query()->firstOrCreate(['name' => Role::SUPERADMIN]);
    $user->roles()->attach($superadminRole);

    return $user->refresh();
}

it('blocks guests from user management', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

it('lets an admin manage accountant and teacher accounts only', function () {
    $admin = adminUser();
    $teacher = userWithRole(Role::TEACHER);
    $privilegedUser = superadminUser();

    $this->actingAs($admin)->get(route('users.show', $teacher))->assertOk();
    $this->actingAs($admin)->get(route('users.show', $privilegedUser))->assertForbidden();
    $this->actingAs($admin)->get(route('users.index'))->assertDontSee($privilegedUser->email);
});

it('lets a superadmin create a user with fixed roles', function () {
    $superadmin = superadminUser();

    $this->actingAs($superadmin)->post(route('users.store'), [
        'name' => 'Finance User',
        'email' => 'finance@example.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'roles' => [Role::ACCOUNTANT],
    ])->assertRedirect();

    $user = User::query()->where('email', 'finance@example.test')->sole();

    expect($user->is_active)->toBeTrue();
    expect($user->hasRole(Role::ACCOUNTANT))->toBeTrue();
});

it('prevents an admin from assigning privileged roles', function () {
    $admin = adminUser();

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Blocked User',
        'email' => 'blocked@example.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'roles' => [Role::ADMIN],
    ])->assertSessionHasErrors('roles.0');

    expect(User::query()->where('email', 'blocked@example.test')->exists())->toBeFalse();
});

it('prevents a superadmin from archiving themselves or the final active superadmin', function () {
    $superadmin = superadminUser();

    $payload = [
        'name' => $superadmin->name,
        'email' => $superadmin->email,
        'roles' => [Role::SUPERADMIN],
        'is_active' => false,
    ];

    $this->actingAs($superadmin)
        ->put(route('users.update', $superadmin), $payload)
        ->assertSessionHasErrors('is_active');

    expect($superadmin->refresh()->is_active)->toBeTrue();
});

it('blocks an inactive user from signing in', function () {
    $user = userWithRole(Role::TEACHER);
    $user->update(['is_active' => false]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
