@extends('layouts.app')

@section('title', 'Collect payment')

@section('content')
    <x-page-header title="Collect a payment"
                   subtitle="Enter the family code to open its outstanding due items for manual allocation."
                   eyebrow="Payments" />

    <x-card title="Find family">
        <form method="GET" action="{{ route('payments.collect') }}">
            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="family_code">Family code <span class="req">*</span></label>
                    <input class="form-control" type="text" id="family_code" name="family_code" value="{{ old('family_code') }}" required>
                </div>
            </div>
            <div class="btn-row"><button type="submit" class="btn">Open payment allocation</button></div>
        </form>
    </x-card>
@endsection
