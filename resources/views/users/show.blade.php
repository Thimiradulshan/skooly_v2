@extends('layouts.app')

@section('title', $user->name)

@section('content')
    <div class="breadcrumb"><a href="{{ route('users.index') }}">Users</a><span class="breadcrumb-sep">/</span><span>{{ $user->name }}</span></div>
    <x-page-header :title="$user->name" subtitle="Staff account and current teaching configuration." eyebrow="Staff" />
    <div class="page-actions"><x-button-link :href="route('users.edit', $user)">Edit user</x-button-link><x-button-link :href="route('users.index')" variant="quiet">Back to users</x-button-link></div>
    <x-card title="Account"><dl class="kv"><div class="kv-row"><dt>Email</dt><dd>{{ $user->email }}</dd></div><div class="kv-row"><dt>Roles</dt><dd>{{ $user->roles->pluck('name')->join(', ') }}</dd></div><div class="kv-row"><dt>Status</dt><dd><x-status-badge :value="$user->is_active ? 'active' : 'archived'" /></dd></div></dl></x-card>
    <x-card title="Qualified subjects"><p>{{ $user->qualifiedSubjects->pluck('name')->join(', ') ?: 'None' }}</p></x-card>
@endsection
