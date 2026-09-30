@extends('layouts.app')

@section('title', 'Create family')

@section('content')
    <x-page-header title="Create family"
                   subtitle="A family is the household that is billed for its students." />

    <x-card>
        <form method="POST" action="{{ route('families.store') }}">
            @csrf

            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="family_code">Family code <span class="req">*</span></label>
                    <input class="form-control" type="text" id="family_code" name="family_code"
                           value="{{ old('family_code') }}" required autofocus>
                    <span class="form-help">Must be unique across all families.</span>
                </div>

                <div class="form-field">
                    <label class="form-label" for="home_contact_no">Home contact number</label>
                    <input class="form-control" type="text" id="home_contact_no" name="home_contact_no"
                           value="{{ old('home_contact_no') }}">
                </div>

                <div class="form-field form-field-full">
                    <label class="form-label" for="address">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2">{{ old('address') }}</textarea>
                </div>
            </div>

            <div class="checkbox-field">
                <input type="checkbox" id="combined_billing_enabled" name="combined_billing_enabled" value="1"
                       {{ old('combined_billing_enabled', '1') ? 'checked' : '' }}>
                <label for="combined_billing_enabled">Combined billing enabled</label>
            </div>

            <h2 class="section-heading">First guardian (optional)</h2>
            <p class="note">Guardians are not linked to students automatically. That happens during student registration.</p>

            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="guardian_name">Guardian name</label>
                    <input class="form-control" type="text" id="guardian_name" name="guardians[0][name]"
                           value="{{ old('guardians.0.name') }}">
                </div>

                <div class="form-field">
                    <label class="form-label" for="guardian_relationship">Relationship</label>
                    <input class="form-control" type="text" id="guardian_relationship" name="guardians[0][relationship]"
                           value="{{ old('guardians.0.relationship') }}">
                </div>

                <div class="form-field">
                    <label class="form-label" for="guardian_contact_no">Contact number</label>
                    <input class="form-control" type="text" id="guardian_contact_no" name="guardians[0][contact_no]"
                           value="{{ old('guardians.0.contact_no') }}">
                </div>

                <div class="form-field">
                    <label class="form-label" for="guardian_email">Email</label>
                    <input class="form-control" type="email" id="guardian_email" name="guardians[0][email]"
                           value="{{ old('guardians.0.email') }}">
                </div>

                <div class="form-field">
                    <label class="form-label" for="guardian_nic">NIC</label>
                    <input class="form-control" type="text" id="guardian_nic" name="guardians[0][nic]"
                           value="{{ old('guardians.0.nic') }}">
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn">Create family</button>
                <a class="btn btn-secondary" href="{{ route('families.index') }}">Cancel</a>
            </div>
        </form>
    </x-card>
@endsection
