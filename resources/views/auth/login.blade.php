@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
    <div class="auth-layout">
        <section class="auth-story">
            <div class="auth-story-brand">
                <span class="brand-mark">S</span>
                <span>Skooly</span>
            </div>

            <div class="auth-story-copy">
                <span class="auth-kicker">School operations, organised</span>
                <h1 class="auth-story-title">Run the school day with clarity.</h1>
                <p class="auth-story-text">
                    Registration, fees, collections, events, reminders, and promotion — one secure workspace for the school office.
                </p>
            </div>

            <p class="auth-story-foot">Private administration workspace</p>
        </section>

        <section class="auth-form-side">
            <div class="auth-card" data-testid="login-card">
                <div class="auth-card-head">
                    <h1 class="auth-brand">Welcome back</h1>
                    <p class="auth-subtitle">Sign in to Skooly School Management Admin.</p>
                </div>

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="form-field">
                        <label class="form-label" for="email">Email address</label>
                        <input class="form-control" type="email" id="email" name="email"
                               value="{{ old('email') }}" required autofocus autocomplete="username"
                               placeholder="admin@skooly.test">
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control" type="password" id="password" name="password"
                               required autocomplete="current-password" placeholder="Enter your password">
                    </div>

                    <button type="submit" class="btn">Sign in</button>
                </form>

                <p class="auth-note"><a href="{{ route('password.request') }}">Forgot your password?</a></p>
                <p class="auth-note">Local demo: admin&#64;skooly.test / password</p>
            </div>
        </section>
    </div>
@endsection
