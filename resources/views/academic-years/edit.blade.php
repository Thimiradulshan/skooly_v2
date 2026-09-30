@extends('layouts.app')

@section('title', 'Edit '.$academicYear->name)

@section('content')
    <x-page-header :title="'Edit '.$academicYear->name" subtitle="Update this academic year's name or dates." eyebrow="Academic setup" />
    <x-card><form method="POST" action="{{ route('academic-years.update', $academicYear) }}" data-loading>@csrf @method('PUT')
        <div class="form-field"><label class="form-label" for="name">Name <span class="req">*</span></label><input class="form-control" type="text" id="name" name="name" value="{{ old('name', $academicYear->name) }}" required autofocus></div>
        <div class="form-grid"><div class="form-field"><label class="form-label" for="start_date">Start date <span class="req">*</span></label><input class="form-control" type="date" id="start_date" name="start_date" value="{{ old('start_date', $academicYear->start_date->toDateString()) }}" required></div><div class="form-field"><label class="form-label" for="end_date">End date <span class="req">*</span></label><input class="form-control" type="date" id="end_date" name="end_date" value="{{ old('end_date', $academicYear->end_date->toDateString()) }}" required></div></div>
        <div class="btn-row"><button type="submit" class="btn">Save academic year</button><a class="btn btn-secondary" href="{{ route('academic-years.show', $academicYear) }}">Cancel</a></div>
    </form></x-card>
@endsection
