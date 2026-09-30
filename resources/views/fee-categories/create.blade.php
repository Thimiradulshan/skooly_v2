@extends('layouts.app')

@section('title', 'Create fee category')
@section('heading', 'Create fee category')

@section('content')
    <form method="POST" action="{{ route('fee-categories.store') }}">
        @csrf

        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required>

        <label for="is_recurring">
            <input type="checkbox" id="is_recurring" name="is_recurring" value="1"
                   {{ old('is_recurring') ? 'checked' : '' }}>
            Recurring
        </label>

        <label for="is_opt_in">
            <input type="checkbox" id="is_opt_in" name="is_opt_in" value="1"
                   {{ old('is_opt_in') ? 'checked' : '' }}>
            Opt-in (requires a student subscription, for example transport)
        </label>

        <p>
            <button type="submit">Create fee category</button>
            <a href="{{ route('fee-categories.index') }}">Cancel</a>
        </p>
    </form>
@endsection
