@extends('layouts.app')

@section('title', $section->grade->name.' '.$section->name)

@section('content')
    <div class="breadcrumb"><a href="{{ route('sections.index') }}">Sections</a><span class="breadcrumb-sep">/</span><span>{{ $section->grade->name }} {{ $section->name }}</span></div>
    <x-page-header :title="$section->grade->name.' '.$section->name" subtitle="Section details." eyebrow="Academic setup" />
    <div class="page-actions"><x-button-link :href="route('sections.edit', $section)">Edit section</x-button-link><x-button-link :href="route('sections.index')" variant="quiet">Back to sections</x-button-link></div>
    <x-card title="Details"><dl class="kv"><div class="kv-row"><dt>Grade</dt><dd><a href="{{ route('grades.show', $section->grade) }}">{{ $section->grade->name }}</a></dd></div><div class="kv-row"><dt>Section</dt><dd>{{ $section->name }}</dd></div><div class="kv-row"><dt>Capacity</dt><dd>{{ $section->capacity ?? 'Not set' }}</dd></div></dl></x-card>
@endsection
