@extends('layouts.app')

@section('title', 'School settings')

@section('content')
    <x-page-header title="School settings" subtitle="Choose the academic year shown as active for the school." eyebrow="Academic setup" />
    <x-card title="Active academic year"><form method="POST" action="{{ route('school-settings.update') }}" data-loading>@csrf @method('PUT')
        <div class="form-field"><label class="form-label" for="active_academic_year_id">Active academic year <span class="req">*</span></label><select class="form-control" id="active_academic_year_id" name="active_academic_year_id" required>@foreach ($academicYears as $academicYear)<option value="{{ $academicYear->id }}" @selected(old('active_academic_year_id', $schoolSetting->active_academic_year_id) == $academicYear->id)>{{ $academicYear->name }}</option>@endforeach</select><span class="form-help">This setting is stored now; no existing workflow automatically changes its behaviour based on the active year.</span></div>
        <div class="btn-row"><button type="submit" class="btn">Save school settings</button><a class="btn btn-secondary" href="{{ route('admin.dashboard') }}">Back to dashboard</a></div>
    </form></x-card>
@endsection
