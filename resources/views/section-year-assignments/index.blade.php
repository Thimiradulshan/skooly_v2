@extends('layouts.app')

@section('title', 'Class in charge')

@section('content')
    <x-page-header title="Class in charge" subtitle="Read-only yearly assignments of a Teacher responsible for each section." eyebrow="Staff" />
    <div class="page-actions"><x-button-link :href="route('section-year-assignments.create')">Assign class in charge</x-button-link></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Academic year</th><th>Section</th><th>Class in charge</th><th class="actions">Actions</th></tr></thead><tbody>@forelse ($assignments as $assignment)<tr><td>{{ $assignment->academicYear->name }}</td><td>{{ $assignment->section->grade->name }} {{ $assignment->section->name }}</td><td>{{ $assignment->classInCharge->name }}</td><td class="actions"><x-button-link :href="route('section-year-assignments.show', $assignment)" variant="quiet" size="small">View</x-button-link></td></tr>@empty<tr class="table-empty"><td colspan="4">No class-in-charge assignments yet.</td></tr>@endforelse</tbody></table></div>
@endsection
