@extends('layouts.app')

@section('title', $grade->name)

@section('content')
    <div class="breadcrumb"><a href="{{ route('grades.index') }}">Grades</a><span class="breadcrumb-sep">/</span><span>{{ $grade->name }}</span></div>
    <x-page-header :title="$grade->name" subtitle="Grade details and its configured sections." eyebrow="Academic setup" />
    <div class="page-actions"><x-button-link :href="route('grades.edit', $grade)">Edit grade</x-button-link><x-button-link :href="route('sections.create')" variant="secondary">Add section</x-button-link><x-button-link :href="route('grades.index')" variant="quiet">Back to grades</x-button-link></div>
    <x-card title="Details"><dl class="kv"><div class="kv-row"><dt>Promotion sequence</dt><dd>{{ $grade->sequence_order }}</dd></div></dl></x-card>
    <x-card title="Sections"><div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Capacity</th><th class="actions">Actions</th></tr></thead><tbody>@forelse ($grade->sections as $section)<tr><td>{{ $section->name }}</td><td>{{ $section->capacity ?? 'Not set' }}</td><td class="actions"><x-button-link :href="route('sections.show', $section)" variant="quiet" size="small">View</x-button-link></td></tr>@empty<tr class="table-empty"><td colspan="3"><span class="empty-state-title">No sections yet</span>Add the first section for this grade.</td></tr>@endforelse</tbody></table></div></x-card>
@endsection
