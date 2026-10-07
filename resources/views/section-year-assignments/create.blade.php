@extends('layouts.app')

@section('title', 'Assign class in charge')

@section('content')
    <x-page-header title="Assign class in charge" subtitle="Assign one active Teacher to be responsible for a section during an academic year." eyebrow="Staff" />
    <x-card><form method="POST" action="{{ route('section-year-assignments.store') }}" data-loading>@csrf
        <div class="form-field"><label class="form-label" for="academic_year_id">Academic year <span class="req">*</span></label><select class="form-control" id="academic_year_id" name="academic_year_id" required><option value="">-- select --</option>@foreach ($academicYears as $academicYear)<option value="{{ $academicYear->id }}" @selected(old('academic_year_id', $academicYears->sole()->id) == $academicYear->id)>{{ $academicYear->name }}</option>@endforeach</select></div>
        <div class="form-field"><label class="form-label" for="section_id">Section <span class="req">*</span></label><select class="form-control" id="section_id" name="section_id" required><option value="">-- select --</option>@foreach ($sections as $section)<option value="{{ $section->id }}">{{ $section->grade->name }} {{ $section->name }}</option>@endforeach</select></div>
        <div class="form-field"><label class="form-label" for="class_in_charge_id">Teacher <span class="req">*</span></label><select class="form-control" id="class_in_charge_id" name="class_in_charge_id" required><option value="">-- select --</option>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select></div>
        <div class="btn-row"><button type="submit" class="btn">Assign class in charge</button><a class="btn btn-secondary" href="{{ route('section-year-assignments.index') }}">Cancel</a></div>
    </form></x-card>
@endsection
