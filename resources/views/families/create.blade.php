@extends('layouts.app')

@section('title', 'Create family')
@section('heading', 'Create family')

@section('content')
    <form method="POST" action="{{ route('families.store') }}">
        @csrf

        <label for="family_code">Family code</label>
        <input type="text" id="family_code" name="family_code" value="{{ old('family_code') }}" required>

        <label for="address">Address</label>
        <textarea id="address" name="address" rows="2">{{ old('address') }}</textarea>

        <label for="home_contact_no">Home contact number</label>
        <input type="text" id="home_contact_no" name="home_contact_no" value="{{ old('home_contact_no') }}">

        <label for="combined_billing_enabled">
            <input type="checkbox" id="combined_billing_enabled" name="combined_billing_enabled" value="1"
                   {{ old('combined_billing_enabled', '1') ? 'checked' : '' }}>
            Combined billing enabled
        </label>

        <h2>First guardian (optional)</h2>

        <label for="guardian_name">Guardian name</label>
        <input type="text" id="guardian_name" name="guardians[0][name]" value="{{ old('guardians.0.name') }}">

        <label for="guardian_relationship">Relationship</label>
        <input type="text" id="guardian_relationship" name="guardians[0][relationship]"
               value="{{ old('guardians.0.relationship') }}">

        <label for="guardian_contact_no">Contact number</label>
        <input type="text" id="guardian_contact_no" name="guardians[0][contact_no]"
               value="{{ old('guardians.0.contact_no') }}">

        <label for="guardian_email">Email</label>
        <input type="email" id="guardian_email" name="guardians[0][email]" value="{{ old('guardians.0.email') }}">

        <label for="guardian_nic">NIC</label>
        <input type="text" id="guardian_nic" name="guardians[0][nic]" value="{{ old('guardians.0.nic') }}">

        <p>
            <button type="submit">Create family</button>
            <a href="{{ route('families.index') }}">Cancel</a>
        </p>
    </form>
@endsection
