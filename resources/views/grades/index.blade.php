@extends('layouts.app')

@section('title', 'Grades')

@section('content')
    <x-page-header title="Grades" subtitle="Set the persistent grade order used for enrollment and promotion." eyebrow="Academic setup" />
    <div class="page-actions"><x-button-link :href="route('grades.create')">Create grade</x-button-link></div>
    <x-list-search :action="route('grades.index')" label="Grade name" :value="$search" :sort-options="['name' => 'Name', 'sequence' => 'Sequence']" :sort="$sort" :direction="$direction" />
    <div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Status</th><th>Sequence</th><th>Sections</th><th class="actions">Actions</th></tr></thead><tbody>@forelse ($grades as $grade)<tr><td>{{ $grade->name }}</td><td><x-status-badge :value="$grade->is_archived ? 'archived' : 'active'" /></td><td>{{ $grade->sequence_order }}</td><td>{{ $grade->sections_count }}</td><td class="actions"><x-button-link :href="route('grades.show', $grade)" variant="quiet" size="small">View</x-button-link></td></tr>@empty<tr class="table-empty"><td colspan="5"><span class="empty-state-title">No grades yet</span>Create grades in their promotion order before adding sections.<div class="empty-actions"><x-button-link :href="route('grades.create')" size="small">Create grade</x-button-link></div></td></tr>@endforelse</tbody></table></div>
    <x-pagination :paginator="$grades" />
    <p class="note">Sequence order controls the default promotion path. Archived grades remain visible here and in history, but cannot be selected for new configuration.</p>
@endsection
