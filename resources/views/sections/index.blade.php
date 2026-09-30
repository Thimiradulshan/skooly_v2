@extends('layouts.app')

@section('title', 'Sections')

@section('content')
    <x-page-header title="Sections" subtitle="Manage the class sections available within each grade." eyebrow="Academic setup" />
    <div class="page-actions"><x-button-link :href="route('sections.create')">Create section</x-button-link></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Grade</th><th>Capacity</th><th class="actions">Actions</th></tr></thead><tbody>@forelse ($sections as $section)<tr><td>{{ $section->name }}</td><td>{{ $section->grade->name }}</td><td>{{ $section->capacity ?? 'Not set' }}</td><td class="actions"><x-button-link :href="route('sections.show', $section)" variant="quiet" size="small">View</x-button-link></td></tr>@empty<tr class="table-empty"><td colspan="4"><span class="empty-state-title">No sections yet</span>Create a section after setting up its grade.<div class="empty-actions"><x-button-link :href="route('sections.create')" size="small">Create section</x-button-link></div></td></tr>@endforelse</tbody></table></div>
    <p class="note">Archiving and deletion are deferred because the schema does not support a lifecycle status.</p>
@endsection
