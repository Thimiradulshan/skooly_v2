@extends('layouts.app')

@section('title', 'My profile')

@section('content')
    <x-page-header title="My profile" subtitle="Update your display name or password. Email, roles, and linked Guardian records are managed through approved administrative workflows." eyebrow="Account" />
    <div class="page-actions"><x-button-link :href="route('two-factor.show')" variant="secondary">Security &amp; two-factor authentication</x-button-link></div>
    <x-card title="Profile details"><form method="POST" action="{{ route('profile.update') }}" data-loading>@csrf @method('PUT')
        <div class="form-field"><label class="form-label" for="name">Display name <span class="req">*</span></label><input class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required></div>
        <div class="form-field"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" value="{{ $user->email }}" disabled><span class="form-help">Email changes require an approved verification workflow and are not available here.</span></div>
        <h2 class="section-heading">Change password</h2>
        <p class="note">Leave these fields blank to keep the current password.</p>
        <div class="form-grid"><div class="form-field"><label class="form-label" for="current_password">Current password</label><input class="form-control" type="password" id="current_password" name="current_password" autocomplete="current-password"></div><div class="form-field"><label class="form-label" for="new_password">New password</label><input class="form-control" type="password" id="new_password" name="new_password" autocomplete="new-password"></div><div class="form-field"><label class="form-label" for="new_password_confirmation">Confirm new password</label><input class="form-control" type="password" id="new_password_confirmation" name="new_password_confirmation" autocomplete="new-password"></div></div>
        <div class="btn-row"><button type="submit" class="btn">Save profile</button></div>
    </form></x-card>
@endsection
