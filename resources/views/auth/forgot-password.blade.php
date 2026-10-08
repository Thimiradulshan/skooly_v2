@extends('layouts.app')

@section('title', 'Reset password')

@section('content')
    <div class="auth-layout">
        <section class="auth-story">
            <div class="auth-story-brand"><span class="brand-mark">S</span><span>Skooly</span></div>
            <div class="auth-story-copy">
                <span class="auth-kicker">Account recovery</span>
                <h1 class="auth-story-title">Reset your password securely.</h1>
                <p class="auth-story-text">Enter your email address and we will send a reset link if an account matches it.</p>
            </div>
        </section>

        <section class="auth-form-side">
            <div class="auth-card">
                <div class="auth-card-head">
                    <h1 class="auth-brand">Forgot password?</h1>
                    <p class="auth-subtitle">We will email you a secure reset link.</p>
                </div>

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="form-field">
                        <label class="form-label" for="email">Email address</label>
                        <input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
                    </div>
                    <button type="submit" class="btn">Email password reset link</button>
                </form>

                <p class="auth-note"><a href="{{ route('login') }}">Return to sign in</a></p>
            </div>
        </section>
    </div>
@endsection
