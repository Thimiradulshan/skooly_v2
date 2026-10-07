<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('assigns the superadmin role to an existing user', function () {
    $user = User::factory()->create(['email' => 'owner@example.test']);

    $this->artisan('users:make-superadmin', ['email' => $user->email])
        ->expectsOutput('owner@example.test is now a Superadmin.')
        ->assertSuccessful();

    expect($user->refresh()->hasRole(Role::SUPERADMIN))->toBeTrue();
});

it('fails without creating a role for an unknown user', function () {
    $this->artisan('users:make-superadmin', ['email' => 'missing@example.test'])
        ->expectsOutput('No user exists with that email address.')
        ->assertFailed();

    expect(Role::query()->where('name', Role::SUPERADMIN)->exists())->toBeFalse();
});
