@extends('layouts.app')

@section('title', 'Teaching assignment '.$teacherAssignment->id)

@section('content')
    <div class="breadcrumb"><a href="{{ route('teacher-assignments.index') }}">Teaching assignments</a><span class="breadcrumb-sep">/</span><span>Assignment {{ $teacherAssignment->id }}</span></div>
    <x-page-header :title="'Teaching assignment '.$teacherAssignment->id" :subtitle="$canCorrect ? 'Correct the Teacher or subject for the current academic year. Placement remains fixed.' : 'Historical or archived teaching configuration is read-only.'" eyebrow="Staff" />
    <div class="page-actions">@if ($canCorrect)<x-button-link :href="route('teacher-assignments.edit', $teacherAssignment)">Correct assignment</x-button-link>@endif<x-button-link :href="route('teacher-assignments.index')" variant="quiet">Back to teaching assignments</x-button-link></div>
    <x-card title="Assignment"><dl class="kv"><div class="kv-row"><dt>Academic year</dt><dd>{{ $teacherAssignment->academicYear->name }}</dd></div><div class="kv-row"><dt>Section</dt><dd>{{ $teacherAssignment->section->grade->name }} {{ $teacherAssignment->section->name }}</dd></div><div class="kv-row"><dt>Subject</dt><dd>{{ $teacherAssignment->subject->name }}</dd></div><div class="kv-row"><dt>Teacher</dt><dd>{{ $teacherAssignment->teacher->name }}</dd></div></dl></x-card>
@endsection
