@extends('layouts.app')

@section('title', 'Sections')

@section('content')
    <x-page-header title="Sections" subtitle="Manage the class sections available within each grade." eyebrow="Academic setup" />
    <div class="page-actions"><x-button-link :href="route('sections.create')">Create section</x-button-link></div>
    <x-list-search :action="route('sections.index')" label="Section or grade name" :value="$search" :sort-options="['name' => 'Name', 'capacity' => 'Capacity']" :sort="$sort" :direction="$direction" />
    <div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Status</th><th>Grade</th><th>Capacity</th><th class="actions">Actions</th></tr></thead><tbody>@forelse ($sections as $section)<tr><td>{{ $section->name }}</td><td><x-status-badge :value="$section->is_archived ? 'archived' : 'active'" /></td><td>{{ $section->grade->name }}</td><td>{{ $section->capacity ?? 'Not set' }}</td><td class="actions"><x-button-link :href="route('sections.show', $section)" variant="quiet" size="small">View</x-button-link></td></tr>@empty<tr class="table-empty"><td colspan="5"><span class="empty-state-title">No sections yet</span>Create a section after setting up its grade.<div class="empty-actions"><x-button-link :href="route('sections.create')" size="small">Create section</x-button-link></div></td></tr>@endforelse</tbody></table></div>
    <x-pagination :paginator="$sections" />
    <p class="note">Archived sections remain visible here and in history, but cannot be selected for new configuration.</p>
@endsection
