@extends('layouts.app')

@section('title', 'Edit '.$family->family_code)
@section('heading', 'Edit family '.$family->family_code)

@section('content')
    <form method="POST" action="{{ route('families.update', $family) }}">
        @csrf
        @method('PUT')

        <label for="family_code">Family code</label>
        <input type="text" id="family_code" name="family_code" value="{{ old('family_code', $family->family_code) }}" required>

        <label for="address">Address</label>
        <textarea id="address" name="address" rows="2">{{ old('address', $family->address) }}</textarea>

        <label for="home_contact_no">Home contact number</label>
        <input type="text" id="home_contact_no" name="home_contact_no"
               value="{{ old('home_contact_no', $family->home_contact_no) }}">

        <label for="combined_billing_enabled">
            <input type="checkbox" id="combined_billing_enabled" name="combined_billing_enabled" value="1"
                   {{ old('combined_billing_enabled', $family->combined_billing_enabled) ? 'checked' : '' }}>
            Combined billing enabled
        </label>

        <p>
            <button type="submit">Save family</button>
            <a href="{{ route('families.show', $family) }}">Cancel</a>
        </p>
    </form>
@endsection
