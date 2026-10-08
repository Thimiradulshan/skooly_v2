<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use OTPHP\TOTP;

uses(LazilyRefreshDatabase::class);

function userWithTwoFactorAuthentication(string $secret, array $recoveryCodes = []): User
{
    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => array_map(Hash::make(...), $recoveryCodes),
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $user;
}

it('displays an authenticator setup URI and manual secret before setup is confirmed', function () {
    $user = User::factory()->create();
    $secret = TOTP::generate()->getSecret();

    $this->actingAs($user)
        ->withSession(['two_factor_setup_secret' => $secret])
        ->get(route('two-factor.show'))
        ->assertOk()
        ->assertSee($secret)
        ->assertSee('otpauth://totp/Skooly%3A'.rawurlencode($user->email), false);
});

it('confirms setup with a valid code and stores only encrypted secrets and hashed recovery codes', function () {
    $user = User::factory()->create();
    $secret = TOTP::generate()->getSecret();

    $this->actingAs($user)
        ->withSession(['two_factor_setup_secret' => $secret])
        ->post(route('two-factor.confirm'), ['code' => TOTP::createFromSecret($secret)->now()])
        ->assertRedirect(route('two-factor.show'));

    $user->refresh();
    $raw = DB::table('users')->where('id', $user->id)->first(['two_factor_secret', 'two_factor_recovery_codes']);

    expect($user->two_factor_secret)->toBe($secret)
        ->and($user->two_factor_confirmed_at)->not->toBeNull()
        ->and($user->two_factor_recovery_codes)->toHaveCount(8)
        ->and($raw->two_factor_secret)->not->toBe($secret)
        ->and($raw->two_factor_recovery_codes)->not->toContain($secret);

    foreach ($user->two_factor_recovery_codes as $recoveryCode) {
        expect($recoveryCode)->toStartWith('$');
    }
});

it('requires a second factor after a valid password and regenerates an authenticated session only after a valid totp code', function () {
    $secret = TOTP::generate()->getSecret();
    $user = userWithTwoFactorAuthentication($secret);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.challenge'));

    $this->assertGuest();

    $this->post(route('two-factor.challenge.store'), ['code' => TOTP::createFromSecret($secret)->now()])
        ->assertRedirect(route('families.index'));

    $this->assertAuthenticatedAs($user);
});

it('keeps a two-factor challenge unauthenticated when the code is invalid', function () {
    $user = userWithTwoFactorAuthentication(TOTP::generate()->getSecret());

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->post(route('two-factor.challenge.store'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

it('consumes a recovery code when it completes a two-factor challenge', function () {
    $recoveryCode = 'A1B2C3D4E5';
    $user = userWithTwoFactorAuthentication(TOTP::generate()->getSecret(), [$recoveryCode]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->post(route('two-factor.challenge.store'), ['code' => $recoveryCode])
        ->assertRedirect(route('families.index'));

    expect($user->refresh()->two_factor_recovery_codes)->toBe([]);

    $this->post(route('logout'));
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->post(route('two-factor.challenge.store'), ['code' => $recoveryCode])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

it('rate limits repeated two-factor challenge failures', function () {
    $secret = TOTP::generate()->getSecret();
    $user = userWithTwoFactorAuthentication($secret);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('two-factor.challenge.store'), ['code' => '000000'])->assertSessionHasErrors('code');
    }

    $this->post(route('two-factor.challenge.store'), ['code' => TOTP::createFromSecret($secret)->now()])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

it('disables two-factor authentication only after a valid password and second factor', function () {
    $secret = TOTP::generate()->getSecret();
    $user = userWithTwoFactorAuthentication($secret, ['A1B2C3D4E5']);

    $this->actingAs($user)
        ->delete(route('two-factor.destroy'), [
            'password' => 'password',
            'code' => TOTP::createFromSecret($secret)->now(),
        ])
        ->assertRedirect(route('two-factor.show'));

    $user->refresh();

    expect($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();
});
