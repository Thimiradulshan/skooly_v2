@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
    <div class="auth-card">
        <h1 class="auth-brand">Skooly</h1>
        <p class="auth-subtitle">School Management Admin</p>

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <div class="form-field">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email"
                       value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>

            <div class="form-field">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password"
                       required autocomplete="current-password">
            </div>

            <button type="submit" class="btn">Sign in</button>
        </form>

        <p class="auth-note">Local demo account: admin&#64;skooly.test</p>
    </div>
@endsection
