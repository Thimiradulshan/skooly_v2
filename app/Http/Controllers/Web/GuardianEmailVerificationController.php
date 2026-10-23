<?php

namespace App\Http\Controllers\Web;

use App\Actions\Guardians\RequestGuardianEmailVerification;
use App\Actions\Guardians\VerifyGuardianEmail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\RequestGuardianEmailVerificationRequest;
use App\Models\Guardian;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class GuardianEmailVerificationController extends Controller
{
    public function store(RequestGuardianEmailVerificationRequest $request, Guardian $guardian, RequestGuardianEmailVerification $action): RedirectResponse
    {
        try {
            $action->handle($guardian, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['verification' => $exception->getMessage()]);
        }

        return back()->with('status', 'A verification link has been sent to the Guardian email address.');
    }

    public function verify(string $token, VerifyGuardianEmail $action): RedirectResponse
    {
        if (! $action->handle($token)) {
            return redirect()->route('login')->withErrors(['verification' => 'This verification link is invalid or expired.']);
        }

        return redirect()->route('login')->with('status', 'Your email address has been verified.');
    }
}
