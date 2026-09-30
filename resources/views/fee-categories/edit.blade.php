@extends('layouts.app')

@section('title', 'Edit '.$feeCategory->name)
@section('heading', 'Edit fee category')

@section('content')
    <form method="POST" action="{{ route('fee-categories.update', $feeCategory) }}">
        @csrf
        @method('PUT')

        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="{{ old('name', $feeCategory->name) }}" required>

        <label for="is_recurring">
            <input type="checkbox" id="is_recurring" name="is_recurring" value="1"
                   {{ old('is_recurring', $feeCategory->is_recurring) ? 'checked' : '' }}>
            Recurring
        </label>

        <label for="is_opt_in">
            <input type="checkbox" id="is_opt_in" name="is_opt_in" value="1"
                   {{ old('is_opt_in', $feeCategory->is_opt_in) ? 'checked' : '' }}>
            Opt-in
        </label>

        <p>
            <button type="submit">Save fee category</button>
            <a href="{{ route('fee-categories.index') }}">Cancel</a>
        </p>
    </form>
@endsection
