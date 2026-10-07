@extends('layouts.app')

@section('title', 'Users')

@section('content')
    <x-page-header title="Users" subtitle="Manage staff accounts and their fixed roles." eyebrow="Staff" />
    <div class="page-actions"><x-button-link :href="route('users.create')">Create user</x-button-link></div>
    <x-list-search :action="route('users.index')" label="Name or email address" :value="$search" />
    <div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Status</th><th class="actions">Actions</th></tr></thead><tbody>
    @forelse ($users as $user)
        <tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->roles->pluck('name')->join(', ') }}</td><td><x-status-badge :value="$user->is_active ? 'active' : 'archived'" /></td><td class="actions"><x-button-link :href="route('users.show', $user)" variant="quiet" size="small">View</x-button-link></td></tr>
    @empty
        <tr class="table-empty"><td colspan="5"><span class="empty-state-title">No staff accounts yet</span>Create the first staff account and assign its role.</td></tr>
    @endforelse
    </tbody></table></div>
    <x-pagination :paginator="$users" />
    <p class="note">Accounts are archived rather than deleted. Admins cannot manage Admin or Superadmin accounts.</p>
@endsection
