@extends('layouts.app')

@section('title', $subject->name)

@section('content')
    <div class="breadcrumb"><a href="{{ route('subjects.index') }}">Subjects</a><span class="breadcrumb-sep">/</span><span>{{ $subject->name }}</span></div>
    <x-page-header :title="$subject->name" :subtitle="$subject->code" eyebrow="Academic setup" />
    <div class="page-actions"><x-button-link :href="route('subjects.edit', $subject)">Edit subject</x-button-link><form method="POST" action="{{ route('subjects.destroy', $subject) }}" data-confirm="Delete this subject? This cannot be undone." data-loading>@csrf @method('DELETE')<button type="submit" class="btn btn-danger">Delete subject</button></form><x-button-link :href="route('subjects.index')" variant="quiet">Back to subjects</x-button-link></div>
    <x-card title="Qualified teachers"><p>{{ $subject->qualifiedTeachers->pluck('name')->join(', ') ?: 'None' }}</p></x-card>
    <x-card title="Teaching assignments"><div class="table-wrap"><table class="table"><thead><tr><th>Teacher</th><th>Academic year</th><th>Section</th></tr></thead><tbody>@forelse ($subject->teacherAssignments as $assignment)<tr><td>{{ $assignment->teacher->name }}</td><td>{{ $assignment->academicYear->name }}</td><td>{{ $assignment->section->name }}</td></tr>@empty<tr class="table-empty"><td colspan="3">No teaching assignments.</td></tr>@endforelse</tbody></table></div></x-card>
    <p class="note">Deletion is refused when teaching assignments reference this subject.</p>
@endsection
