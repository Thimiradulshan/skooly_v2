<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        if ($request->user()?->two_factor_confirmed_at !== null) {
            $userId = $request->user()->getKey();

            Auth::logout();
            $request->session()->regenerate();
            $request->session()->put('two_factor_login_id', $userId);

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        if ($request->user()?->hasRole(Role::ADMIN)) {
            return redirect()->intended(route('families.index'));
        }

        return redirect()->to($this->homeRoute($request));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function homeRoute(Request $request): string
    {
        $user = $request->user();

        return match (true) {
            $user?->hasRole(Role::ADMIN) => route('families.index'),
            $user?->hasRole(Role::ACCOUNTANT) => route('payments.collect'),
            $user?->guardian_id !== null => route('guardian.portal'),
            $user?->hasRole(Role::TEACHER) => route('teacher.portal'),
            default => route('login'),
        };
    }
}
