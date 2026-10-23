@extends('layouts.app')
@section('title', 'Teacher portal')
@section('content')
<x-page-header title="Welcome, {{ auth()->user()->name }}" subtitle="Your active academic-year teaching assignments." eyebrow="Teacher portal" />
<div class="page-actions"><x-button-link :href="route('teacher.classes')">My classes</x-button-link><x-button-link :href="route('teacher.subjects')" variant="quiet">My subjects</x-button-link></div>
<x-card title="Active academic year"><p>{{ $academicYear?->name ?? 'No active academic year is configured.' }}</p></x-card>
<x-card title="Subject assignments"><div class="table-wrap"><table class="table"><thead><tr><th>Subject</th><th>Grade</th><th>Section</th></tr></thead><tbody>@forelse($assignments as $assignment)<tr><td>{{ $assignment->subject->name }}</td><td>{{ $assignment->section->grade->name }}</td><td>{{ $assignment->section->name }}</td></tr>@empty<tr class="table-empty"><td colspan="3">No active teaching assignments are configured.</td></tr>@endforelse</tbody></table></div></x-card>
<x-card title="Class in charge"><div class="table-wrap"><table class="table"><thead><tr><th>Grade</th><th>Section</th></tr></thead><tbody>@forelse($sections as $assignment)<tr><td>{{ $assignment->section->grade->name }}</td><td>{{ $assignment->section->name }}</td></tr>@empty<tr class="table-empty"><td colspan="2">No class-in-charge section is configured.</td></tr>@endforelse</tbody></table></div></x-card>
@endsection
