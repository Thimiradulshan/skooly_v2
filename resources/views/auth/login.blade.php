@extends('layouts.app')

@section('title', 'Sign in')
@section('heading', 'Sign in')

@section('content')
    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <p><button type="submit">Sign in</button></p>
    </form>
@endsection
