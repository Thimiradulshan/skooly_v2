@extends('layouts.app')

@section('title', 'Create fee category')

@section('content')
    <x-page-header title="Create fee category" subtitle="Define a chargeable category." />

    <x-card>
        <form method="POST" action="{{ route('fee-categories.store') }}">
            @csrf

            <div class="form-field">
                <label class="form-label" for="name">Name <span class="req">*</span></label>
                <input class="form-control" type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
                <span class="form-help">Must be unique across all fee categories.</span>
            </div>

            <div class="checkbox-field">
                <input type="checkbox" id="is_recurring" name="is_recurring" value="1"
                       {{ old('is_recurring') ? 'checked' : '' }}>
                <label for="is_recurring">Recurring</label>
            </div>

            <div class="checkbox-field">
                <input type="checkbox" id="is_opt_in" name="is_opt_in" value="1"
                       {{ old('is_opt_in') ? 'checked' : '' }}>
                <label for="is_opt_in">Opt-in</label>
                <span class="form-help">Opt-in categories require an active student subscription before they are charged.</span>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn">Create fee category</button>
                <a class="btn btn-secondary" href="{{ route('fee-categories.index') }}">Cancel</a>
            </div>
        </form>
    </x-card>
@endsection
