@extends('layouts.app')

@section('title', 'Create academic year')

@section('content')
    <x-page-header title="Create academic year" subtitle="Add the date range for a school year." eyebrow="Academic setup" />
    <x-card><form method="POST" action="{{ route('academic-years.store') }}" data-loading>@csrf
        <div class="form-field"><label class="form-label" for="name">Name <span class="req">*</span></label><input class="form-control" type="text" id="name" name="name" value="{{ old('name') }}" required autofocus><span class="form-help">For example, 2026/2027. Names must be unique.</span></div>
        <div class="form-grid"><div class="form-field"><label class="form-label" for="start_date">Start date <span class="req">*</span></label><input class="form-control" type="date" id="start_date" name="start_date" value="{{ old('start_date') }}" required></div><div class="form-field"><label class="form-label" for="end_date">End date <span class="req">*</span></label><input class="form-control" type="date" id="end_date" name="end_date" value="{{ old('end_date') }}" required></div></div>
        <div class="btn-row"><button type="submit" class="btn">Create academic year</button><a class="btn btn-secondary" href="{{ route('academic-years.index') }}">Cancel</a></div>
    </form></x-card>
@endsection
