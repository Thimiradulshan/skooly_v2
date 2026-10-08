<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmTwoFactorSetupRequest;
use App\Http\Requests\Auth\DisableTwoFactorAuthenticationRequest;
use App\Http\Requests\Auth\VerifyTwoFactorChallengeRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use OTPHP\TOTP;

class TwoFactorAuthenticationController extends Controller
{
    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $recoveryCodes = $request->session()->pull('two_factor_recovery_codes');

        if ($user->two_factor_confirmed_at !== null) {
            return view('auth.two-factor-authentication', compact('recoveryCodes'));
        }

        $secret = $request->session()->get('two_factor_setup_secret');

        if (! is_string($secret)) {
            $secret = TOTP::generate()->getSecret();
            $request->session()->put('two_factor_setup_secret', $secret);
        }

        $totp = TOTP::createFromSecret($secret);
        $totp->setIssuer('Skooly');
        $totp->setLabel($user->email);

        return view('auth.two-factor-authentication', [
            'manualSecret' => $secret,
            'provisioningUri' => $totp->getProvisioningUri(),
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    public function confirm(ConfirmTwoFactorSetupRequest $request): RedirectResponse
    {
        $secret = $request->session()->get('two_factor_setup_secret');

        if (! is_string($secret) || ! $this->validTotpCode($secret, $request->string('code')->toString())) {
            throw ValidationException::withMessages([
                'code' => 'The provided two-factor authentication code is invalid.',
            ]);
        }

        $recoveryCodes = array_map(
            static fn (): string => strtoupper(bin2hex(random_bytes(5))),
            range(1, 8),
        );

        /** @var User $user */
        $user = $request->user();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => array_map(Hash::make(...), $recoveryCodes),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('two_factor_setup_secret');
        $request->session()->flash('two_factor_recovery_codes', $recoveryCodes);

        return redirect()->route('two-factor.show')->with('status', 'Two-factor authentication has been enabled.');
    }

    public function createChallenge(Request $request): View|RedirectResponse
    {
        if ($this->pendingUser($request) === null) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function storeChallenge(VerifyTwoFactorChallengeRequest $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return redirect()->route('login');
        }

        $key = 'two-factor-challenge|'.$user->getKey().'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'code' => 'Too many two-factor authentication attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        $code = strtoupper($request->string('code')->trim()->toString());

        if (strlen($code) === 6) {
            $valid = $user->two_factor_secret !== null && $this->validTotpCode($user->two_factor_secret, $code);
        } else {
            $user = $this->consumeRecoveryCode($user, $code);
            $valid = $user !== null;
        }

        if (! $valid) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'code' => 'The provided two-factor authentication code is invalid.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->forget('two_factor_login_id');

        if ($user === null) {
            return redirect()->route('login');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('families.index'));
    }

    public function destroy(DisableTwoFactorAuthenticationRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $code = strtoupper($request->string('code')->trim()->toString());

        if (! Hash::check($request->string('password')->toString(), $user->password)
            || ! $this->validTwoFactorCode($user, $code)) {
            throw ValidationException::withMessages([
                'code' => 'The provided password or two-factor authentication code is invalid.',
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $request->session()->forget(['two_factor_setup_secret', 'two_factor_recovery_codes']);

        return redirect()->route('two-factor.show')->with('status', 'Two-factor authentication has been disabled.');
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('two_factor_login_id');

        if (! is_int($userId) && ! ctype_digit((string) $userId)) {
            $request->session()->forget('two_factor_login_id');

            return null;
        }

        return User::query()
            ->whereKey($userId)
            ->where('is_active', true)
            ->whereNotNull('two_factor_confirmed_at')
            ->first();
    }

    private function validTotpCode(string $secret, string $code): bool
    {
        return TOTP::createFromSecret($secret)->verify($code, leeway: 29);
    }

    private function validTwoFactorCode(User $user, string $code): bool
    {
        if (strlen($code) === 6) {
            return $user->two_factor_secret !== null && $this->validTotpCode($user->two_factor_secret, $code);
        }

        foreach ($user->twoFactorRecoveryCodes() as $recoveryCode) {
            if (Hash::check($code, $recoveryCode)) {
                return true;
            }
        }

        return false;
    }

    private function consumeRecoveryCode(User $user, string $code): ?User
    {
        return DB::transaction(function () use ($user, $code): ?User {
            $lockedUser = User::query()->lockForUpdate()->find($user->getKey());

            if ($lockedUser === null || $lockedUser->two_factor_confirmed_at === null) {
                return null;
            }

            $recoveryCodes = $lockedUser->twoFactorRecoveryCodes();

            foreach ($recoveryCodes as $index => $recoveryCode) {
                if (Hash::check($code, $recoveryCode)) {
                    unset($recoveryCodes[$index]);
                    $lockedUser->forceFill([
                        'two_factor_recovery_codes' => array_values($recoveryCodes),
                    ])->save();

                    return $lockedUser;
                }
            }

            return null;
        });
    }
}
