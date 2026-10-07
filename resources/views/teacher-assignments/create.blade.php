@extends('layouts.app')

@section('title', 'Create teaching assignment')

@section('content')
    <x-page-header title="Create teaching assignment" subtitle="Assign a qualified Teacher to a section and subject for one academic year." eyebrow="Staff" />
    <x-card><form method="POST" action="{{ route('teacher-assignments.store') }}" data-loading>@csrf
        @include('teacher-assignments.partials.form')
        <div class="btn-row"><button type="submit" class="btn">Create assignment</button><a class="btn btn-secondary" href="{{ route('teacher-assignments.index') }}">Cancel</a></div>
    </form></x-card>
@endsection
