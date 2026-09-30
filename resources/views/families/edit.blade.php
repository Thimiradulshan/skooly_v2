@extends('layouts.app')

@section('title', 'Edit '.$family->family_code)

@section('content')
    <x-page-header :title="'Edit '.$family->family_code" subtitle="Update the household details." />

    <x-card>
        <form method="POST" action="{{ route('families.update', $family) }}" data-loading>
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="family_code">Family code <span class="req">*</span></label>
                    <input class="form-control" type="text" id="family_code" name="family_code"
                           value="{{ old('family_code', $family->family_code) }}" required autofocus>
                </div>

                <div class="form-field">
                    <label class="form-label" for="home_contact_no">Home contact number</label>
                    <input class="form-control" type="text" id="home_contact_no" name="home_contact_no"
                           value="{{ old('home_contact_no', $family->home_contact_no) }}">
                </div>

                <div class="form-field form-field-full">
                    <label class="form-label" for="address">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2">{{ old('address', $family->address) }}</textarea>
                </div>
            </div>

            <div class="checkbox-field">
                <input type="checkbox" id="combined_billing_enabled" name="combined_billing_enabled" value="1"
                       {{ old('combined_billing_enabled', $family->combined_billing_enabled) ? 'checked' : '' }}>
                <label for="combined_billing_enabled">Combined billing enabled</label>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn">Save family</button>
                <a class="btn btn-secondary" href="{{ route('families.show', $family) }}">Cancel</a>
            </div>
        </form>
    </x-card>
@endsection
