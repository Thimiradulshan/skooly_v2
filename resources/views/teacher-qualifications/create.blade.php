@extends('layouts.app')

@section('title', 'Add teacher qualification')

@section('content')
    <x-page-header title="Add teacher qualification" subtitle="A qualification is required before creating a teaching assignment." eyebrow="Staff" />
    <x-card><form method="POST" action="{{ route('teacher-qualifications.store') }}" data-loading>@csrf
        <div class="form-field"><label class="form-label" for="teacher_id">Teacher <span class="req">*</span></label><select class="form-control" id="teacher_id" name="teacher_id" required><option value="">-- select --</option>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}" {{ (int) old('teacher_id') === $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>@endforeach</select></div>
        <div class="form-field"><label class="form-label" for="subject_id">Subject <span class="req">*</span></label><select class="form-control" id="subject_id" name="subject_id" required><option value="">-- select --</option>@foreach ($subjects as $subject)<option value="{{ $subject->id }}" {{ (int) old('subject_id') === $subject->id ? 'selected' : '' }}>{{ $subject->code }} — {{ $subject->name }}</option>@endforeach</select></div>
        <div class="btn-row"><button type="submit" class="btn">Add qualification</button><a class="btn btn-secondary" href="{{ route('teacher-qualifications.index') }}">Cancel</a></div>
    </form></x-card>
@endsection
