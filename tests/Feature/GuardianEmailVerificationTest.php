<?php

use App\Actions\Guardians\RequestGuardianEmailVerification;
use App\Mail\GuardianEmailVerificationMail;
use App\Models\Family;
use App\Models\Guardian;
use App\Models\GuardianEmailVerificationToken;
use App\Models\Role;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

uses(LazilyRefreshDatabase::class);

function guardianForEmailVerification(): Guardian
{
    return Guardian::factory()->for(Family::factory())->create(['email' => 'guardian@example.test']);
}

function requestedGuardianVerificationToken(Guardian $guardian): string
{
    Mail::fake();
    (new RequestGuardianEmailVerification)->handle($guardian, adminUser());

    $token = null;
    Mail::assertSent(GuardianEmailVerificationMail::class, function (GuardianEmailVerificationMail $mail) use (&$token): bool {
        $token = $mail->token;

        return true;
    });

    return $token;
}

it('verifies a guardian email only after the mailbox token is followed', function () {
    $guardian = guardianForEmailVerification();
    $token = requestedGuardianVerificationToken($guardian);

    $this->get(route('guardians.email-verification.verify', ['token' => $token]))
        ->assertRedirect(route('login'));

    expect($guardian->refresh()->payment_reminder_email_verified_at)->not->toBeNull();
    expect($guardian->payment_reminder_email_opt_in_at)->toBeNull();
    expect(GuardianEmailVerificationToken::query()->sole()->consumed_at)->not->toBeNull();
});

it('rejects incorrect expired and replayed verification tokens', function () {
    $guardian = guardianForEmailVerification();
    $token = requestedGuardianVerificationToken($guardian);

    $this->get(route('guardians.email-verification.verify', ['token' => 'invalid']))->assertSessionHasErrors('verification');
    GuardianEmailVerificationToken::query()->sole()->update(['expires_at' => now()->subSecond()]);
    $this->get(route('guardians.email-verification.verify', ['token' => $token]))->assertSessionHasErrors('verification');
    expect($guardian->refresh()->payment_reminder_email_verified_at)->toBeNull();

    $token = requestedGuardianVerificationToken($guardian);
    $this->get(route('guardians.email-verification.verify', ['token' => $token]));
    $this->get(route('guardians.email-verification.verify', ['token' => $token]))->assertSessionHasErrors('verification');
});

it('invalidates outstanding tokens when an email changes or a new link is requested', function () {
    $guardian = guardianForEmailVerification();
    $firstToken = requestedGuardianVerificationToken($guardian);
    $guardian->update(['email' => 'changed@example.test']);
    $this->get(route('guardians.email-verification.verify', ['token' => $firstToken]))->assertSessionHasErrors('verification');

    $firstToken = requestedGuardianVerificationToken($guardian);
    $secondToken = requestedGuardianVerificationToken($guardian);
    $this->get(route('guardians.email-verification.verify', ['token' => $firstToken]))->assertSessionHasErrors('verification');
    $this->get(route('guardians.email-verification.verify', ['token' => $secondToken]))->assertRedirect(route('login'));
});

it('allows only admins to request verification and throttles repeated requests', function () {
    Mail::fake();
    $guardian = guardianForEmailVerification();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->post(route('guardians.email-verification.store', $guardian))->assertForbidden();

    $admin = adminUser();
    foreach (range(1, 3) as $attempt) {
        $this->actingAs($admin)->post(route('guardians.email-verification.store', $guardian))->assertRedirect();
    }
    $this->actingAs($admin)->post(route('guardians.email-verification.store', $guardian))->assertSessionHasErrors('verification');
    Mail::assertSent(GuardianEmailVerificationMail::class, 3);
    RateLimiter::clear('guardian-email-verification:'.$admin->id.':'.$guardian->id);
});

it('stores only token hashes and does not expose tokens in records or responses', function () {
    $guardian = guardianForEmailVerification();
    $token = requestedGuardianVerificationToken($guardian);
    $stored = DB::table('guardian_email_verification_tokens')->sole();

    expect($stored->token_hash)->not->toBe($token);
    expect(GuardianEmailVerificationToken::query()->sole()->toArray())->not->toHaveKey('token_hash');
    expect($this->get(route('guardians.email-verification.verify', ['token' => 'invalid']))->getContent())->not->toContain($token);
});
