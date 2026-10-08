@extends('layouts.app')

@section('title', 'Verify your email')

@section('content')
    <div class="auth-layout">
        <section class="auth-story">
            <div class="auth-story-brand"><span class="brand-mark">S</span><span>Skooly</span></div>
            <div class="auth-story-copy">
                <span class="auth-kicker">Email verification</span>
                <h1 class="auth-story-title">One more security step.</h1>
                <p class="auth-story-text">Verify that you control this email address before accessing the school workspace.</p>
            </div>
        </section>

        <section class="auth-form-side">
            <div class="auth-card">
                <div class="auth-card-head">
                    <h1 class="auth-brand">Check your inbox</h1>
                    <p class="auth-subtitle">Use the link in the verification email to continue.</p>
                </div>

                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn">Resend verification email</button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Sign out</button>
                </form>
            </div>
        </section>
    </div>
@endsection
