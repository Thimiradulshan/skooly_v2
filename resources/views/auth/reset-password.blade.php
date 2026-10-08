@extends('layouts.app')

@section('title', 'Choose a new password')

@section('content')
    <div class="auth-layout">
        <section class="auth-story">
            <div class="auth-story-brand"><span class="brand-mark">S</span><span>Skooly</span></div>
            <div class="auth-story-copy">
                <span class="auth-kicker">Account recovery</span>
                <h1 class="auth-story-title">Choose a new password.</h1>
                <p class="auth-story-text">Use a strong password that you do not reuse elsewhere.</p>
            </div>
        </section>

        <section class="auth-form-side">
            <div class="auth-card">
                <div class="auth-card-head"><h1 class="auth-brand">Reset password</h1></div>

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="form-field">
                        <label class="form-label" for="email">Email address</label>
                        <input class="form-control" type="email" id="email" name="email" value="{{ request('email') }}" required autofocus autocomplete="email">
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="password">New password</label>
                        <input class="form-control" type="password" id="password" name="password" required autocomplete="new-password">
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="password_confirmation">Confirm new password</label>
                        <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn">Reset password</button>
                </form>
            </div>
        </section>
    </div>
@endsection
