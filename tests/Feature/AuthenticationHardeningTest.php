<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

uses(LazilyRefreshDatabase::class);

it('throttles repeated failed login attempts by normalized email and ip address', function () {
    $user = User::factory()->create(['email' => 'throttle@example.test']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), [
            'email' => $attempt % 2 === 0 ? 'THROTTLE@EXAMPLE.TEST' : $user->email,
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('email');
    }

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('clears failed login attempts after a successful login', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'incorrect-password'])
        ->assertSessionHasErrors('email');
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('families.index'));
    $this->post(route('logout'));

    foreach (range(1, 4) as $attempt) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'incorrect-password'])
            ->assertSessionHasErrors('email');
    }

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('families.index'));
});

it('returns the same password reset response whether an email exists or not', function () {
    Notification::fake();
    $user = User::factory()->create();
    $message = 'If an account matches that email address, we have sent a password reset link.';

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect()
        ->assertSessionHas('status', $message);
    $this->post(route('password.email'), ['email' => 'missing@example.test'])
        ->assertRedirect()
        ->assertSessionHas('status', $message);

    Notification::assertSentTo($user, ResetPassword::class);
});

it('resets a password with a valid broker token', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('login'));

    expect(Hash::check('new-password', (string) $user->refresh()->password))->toBeTrue();
});

it('redirects unverified users away from protected application routes', function () {
    $admin = User::factory()->unverified()->create();
    $admin->roles()->attach(Role::query()->firstOrCreate(['name' => Role::ADMIN]));

    $this->actingAs($admin)
        ->get(route('families.index'))
        ->assertRedirect(route('verification.notice'));
});

it('sends an email verification link to an unverified user', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect()
        ->assertSessionHas('status', 'A new verification link has been sent to your email address.');

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('verifies an email through a valid signed link', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), [
        'id' => $user->id,
        'hash' => sha1($user->getEmailForVerification()),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect(route('families.index'));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
});

it('preserves role boundaries after email verification', function () {
    $teacher = userWithRole(Role::TEACHER);

    $this->actingAs($teacher)->get(route('families.index'))->assertForbidden();
    $this->actingAs(adminUser())->get(route('families.index'))->assertOk();
});
