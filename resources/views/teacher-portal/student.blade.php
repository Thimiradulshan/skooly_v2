@extends('layouts.app')
@section('title', $student->name)
@section('content')
<div class="breadcrumb"><a href="{{ route('teacher.students') }}">My students</a><span class="breadcrumb-sep">/</span><span>{{ $student->name }}</span></div>
<x-page-header :title="$student->name" subtitle="Read-only active-year academic profile." eyebrow="Teacher portal" />
<div class="page-actions"><x-button-link :href="route('teacher.students', ['section_id' => $enrollment->section_id])" variant="quiet">Back to my students</x-button-link></div>
<x-card title="Student"><dl class="kv"><div class="kv-row"><dt>Admission number</dt><dd>{{ $student->admission_no }}</dd></div><div class="kv-row"><dt>Status</dt><dd><x-status-badge :value="$student->status" /></dd></div><div class="kv-row"><dt>Academic year</dt><dd>{{ $year->name }}</dd></div><div class="kv-row"><dt>Grade</dt><dd>{{ $enrollment->grade->name }}</dd></div><div class="kv-row"><dt>Section</dt><dd>{{ $enrollment->section->name }}</dd></div></dl></x-card>
<x-card title="Active-year placement history"><div class="table-wrap"><table class="table"><thead><tr><th>Grade</th><th>Section</th><th>Recorded</th></tr></thead><tbody>@forelse($enrollment->placements as $placement)<tr><td>{{ $placement->grade->name }}</td><td>{{ $placement->section->name }}</td><td>{{ $placement->created_at?->toDateString() }}</td></tr>@empty<tr class="table-empty"><td colspan="3">No placement changes have been recorded.</td></tr>@endforelse</tbody></table></div></x-card>
@endsection
