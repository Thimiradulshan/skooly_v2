@extends('layouts.app')

@section('title', 'Teaching assignments')

@section('content')
    <x-page-header title="Teaching assignments" subtitle="Read-only history of Teachers assigned to a subject, section, and academic year." eyebrow="Staff" />
    <div class="page-actions"><x-button-link :href="route('teacher-assignments.create')">Create teaching assignment</x-button-link></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Academic year</th><th>Section</th><th>Subject</th><th>Teacher</th><th class="actions">Actions</th></tr></thead><tbody>@forelse ($assignments as $assignment)<tr><td>{{ $assignment->academicYear->name }}</td><td>{{ $assignment->section->grade->name }} {{ $assignment->section->name }}</td><td>{{ $assignment->subject->name }}</td><td>{{ $assignment->teacher->name }}</td><td class="actions"><x-button-link :href="route('teacher-assignments.show', $assignment)" variant="quiet" size="small">View</x-button-link></td></tr>@empty<tr class="table-empty"><td colspan="5">No teaching assignments yet. Qualify a Teacher for a subject first.</td></tr>@endforelse</tbody></table></div>
@endsection
