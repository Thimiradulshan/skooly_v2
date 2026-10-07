@extends('layouts.app')

@section('title', $section->grade->name.' '.$section->name)

@section('content')
    <div class="breadcrumb"><a href="{{ route('sections.index') }}">Sections</a><span class="breadcrumb-sep">/</span><span>{{ $section->grade->name }} {{ $section->name }}</span></div>
    <x-page-header :title="$section->grade->name.' '.$section->name" subtitle="Section details." eyebrow="Academic setup" />
    <div class="page-actions"><x-button-link :href="route('sections.edit', $section)">Edit section</x-button-link><x-button-link :href="route('sections.index')" variant="quiet">Back to sections</x-button-link></div>
    <x-card title="Details"><dl class="kv"><div class="kv-row"><dt>Status</dt><dd><x-status-badge :value="$section->is_archived ? 'archived' : 'active'" /></dd></div><div class="kv-row"><dt>Grade</dt><dd><a href="{{ route('grades.show', $section->grade) }}">{{ $section->grade->name }}</a></dd></div><div class="kv-row"><dt>Section</dt><dd>{{ $section->name }}</dd></div><div class="kv-row"><dt>Capacity</dt><dd>{{ $section->capacity ?? 'Not set' }}</dd></div></dl></x-card>
    <form method="POST" action="{{ $section->is_archived ? route('sections.restore', $section) : route('sections.archive', $section) }}" data-loading @unless ($section->is_archived) data-confirm="Archive this section? Existing assignments, enrollments, and history will remain available." @endunless>@csrf<button type="submit" class="btn {{ $section->is_archived ? 'btn-secondary' : 'btn-danger' }}">{{ $section->is_archived ? 'Restore section' : 'Archive section' }}</button></form>
@endsection
