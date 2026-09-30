@extends('layouts.app')

@section('title', 'Edit '.$feeCategory->name)

@section('content')
    <x-page-header :title="'Edit '.$feeCategory->name" subtitle="Update the category behaviour." />

    <x-card>
        <form method="POST" action="{{ route('fee-categories.update', $feeCategory) }}">
            @csrf
            @method('PUT')

            <div class="form-field">
                <label class="form-label" for="name">Name <span class="req">*</span></label>
                <input class="form-control" type="text" id="name" name="name"
                       value="{{ old('name', $feeCategory->name) }}" required autofocus>
            </div>

            <div class="checkbox-field">
                <input type="checkbox" id="is_recurring" name="is_recurring" value="1"
                       {{ old('is_recurring', $feeCategory->is_recurring) ? 'checked' : '' }}>
                <label for="is_recurring">Recurring</label>
            </div>

            <div class="checkbox-field">
                <input type="checkbox" id="is_opt_in" name="is_opt_in" value="1"
                       {{ old('is_opt_in', $feeCategory->is_opt_in) ? 'checked' : '' }}>
                <label for="is_opt_in">Opt-in</label>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn">Save fee category</button>
                <a class="btn btn-secondary" href="{{ route('fee-categories.index') }}">Cancel</a>
            </div>
        </form>
    </x-card>
@endsection
