@extends('layouts.app')
@section('title', 'My subjects')
@section('content')
<x-page-header title="My subjects" :subtitle="$academicYear ? 'Active year: '.$academicYear->name : 'No active academic year is configured.'" eyebrow="Teacher portal" />
<div class="page-actions"><x-button-link :href="route('teacher.portal')" variant="quiet">Dashboard</x-button-link><x-button-link :href="route('teacher.classes')" variant="quiet">My classes</x-button-link></div>
<x-card title="Subject assignments"><div class="table-wrap"><table class="table"><thead><tr><th>Subject</th><th>Grade</th><th>Section</th></tr></thead><tbody>@forelse($assignments as $assignment)<tr><td>{{ $assignment->subject->name }}</td><td>{{ $assignment->section->grade->name }}</td><td>{{ $assignment->section->name }}</td></tr>@empty<tr class="table-empty"><td colspan="3">No active subject assignments are configured.</td></tr>@endforelse</tbody></table></div></x-card>
@endsection
