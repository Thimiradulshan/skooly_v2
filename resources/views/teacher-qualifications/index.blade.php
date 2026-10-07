@extends('layouts.app')

@section('title', 'Teacher qualifications')

@section('content')
    <x-page-header title="Teacher qualifications" subtitle="Record the subjects each Teacher is qualified to teach." eyebrow="Staff" />
    <div class="page-actions"><x-button-link :href="route('teacher-qualifications.create')">Add qualification</x-button-link></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Teacher</th><th>Qualified subjects</th><th class="actions">Actions</th></tr></thead><tbody>@forelse ($teachers as $teacher)<tr><td>{{ $teacher->name }}</td><td>{{ $teacher->qualifiedSubjects->pluck('name')->join(', ') ?: 'None' }}</td><td class="actions"><x-button-link :href="route('teacher-qualifications.show', $teacher)" variant="quiet" size="small">View</x-button-link></td></tr>@empty<tr class="table-empty"><td colspan="3">No active Teachers exist yet. Create a Teacher user first.</td></tr>@endforelse</tbody></table></div>
@endsection
