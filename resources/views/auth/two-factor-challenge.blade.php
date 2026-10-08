@extends('layouts.app')

@section('title', 'Two-factor authentication')

@section('content')
    <div class="auth-layout">
        <section class="auth-story">
            <div class="auth-story-brand">
                <span class="brand-mark">S</span>
                <span>Skooly</span>
            </div>
            <div class="auth-story-copy">
                <span class="auth-kicker">Account security</span>
                <h1 class="auth-story-title">Verify your sign-in.</h1>
                <p class="auth-story-text">Enter a code from your authenticator app or one of your recovery codes.</p>
            </div>
        </section>

        <section class="auth-form-side">
            <div class="auth-card">
                <div class="auth-card-head">
                    <h1 class="auth-brand">Two-factor authentication</h1>
                    <p class="auth-subtitle">Your password was accepted. One more step is required to sign in.</p>
                </div>

                <form method="POST" action="{{ route('two-factor.challenge.store') }}">
                    @csrf

                    <div class="form-field">
                        <label class="form-label" for="code">Authentication code</label>
                        <input class="form-control" type="text" id="code" name="code" required autofocus autocomplete="one-time-code" inputmode="numeric">
                    </div>

                    <button type="submit" class="btn">Verify and sign in</button>
                </form>
            </div>
        </section>
    </div>
@endsection
