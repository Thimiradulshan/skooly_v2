@extends('layouts.app')
@section('title', 'Place '.$enrollment->student->name)
@section('content')
    <x-page-header :title="'Place '.$enrollment->student->name" :subtitle="'Record a new placement for '.$enrollment->academicYear->name.'. The previous placement remains in history.'" eyebrow="Enrollment" />
    <x-card><form method="POST" action="{{ route('enrollments.placements.store', $enrollment) }}" data-loading>@csrf
        <div class="form-field"><label class="form-label" for="grade_id">Grade <span class="req">*</span></label><select class="form-control" id="grade_id" name="grade_id" required><option value="">-- select --</option>@foreach ($grades as $grade)<option value="{{ $grade->id }}" {{ $enrollment->grade_id === $grade->id ? 'selected' : '' }}>{{ $grade->name }}</option>@endforeach</select></div>
        <div class="form-field"><label class="form-label" for="section_id">Section <span class="req">*</span></label><select class="form-control" id="section_id" name="section_id" required><option value="">-- select --</option>@foreach ($grades as $grade)@foreach ($grade->sections as $section)<option value="{{ $section->id }}" {{ $enrollment->section_id === $section->id ? 'selected' : '' }}>{{ $grade->name }} {{ $section->name }}</option>@endforeach@endforeach</select></div>
        <div class="btn-row"><button type="submit" class="btn">Record placement</button><a class="btn btn-secondary" href="{{ route('students.show', $enrollment->student) }}">Cancel</a></div>
    </form></x-card>
@endsection
