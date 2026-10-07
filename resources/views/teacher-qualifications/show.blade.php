@extends('layouts.app')

@section('title', 'Qualifications for '.$teacher->name)

@section('content')
    <div class="breadcrumb"><a href="{{ route('teacher-qualifications.index') }}">Teacher qualifications</a><span class="breadcrumb-sep">/</span><span>{{ $teacher->name }}</span></div>
    <x-page-header :title="$teacher->name" subtitle="Subjects this Teacher is qualified to teach." eyebrow="Staff" />
    <div class="page-actions"><x-button-link :href="route('teacher-qualifications.create')">Add qualification</x-button-link><x-button-link :href="route('teacher-qualifications.index')" variant="quiet">Back to qualifications</x-button-link></div>
    <x-card title="Qualified subjects"><div class="table-wrap"><table class="table"><thead><tr><th>Code</th><th>Subject</th></tr></thead><tbody>@forelse ($teacher->qualifiedSubjects as $subject)<tr><td>{{ $subject->code }}</td><td>{{ $subject->name }}</td></tr>@empty<tr class="table-empty"><td colspan="2">No qualifications recorded.</td></tr>@endforelse</tbody></table></div></x-card>
@endsection
