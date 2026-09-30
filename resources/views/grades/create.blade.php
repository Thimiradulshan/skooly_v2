@extends('layouts.app')

@section('title', 'Create grade')

@section('content')
    <x-page-header title="Create grade" subtitle="Add a grade and its promotion sequence." eyebrow="Academic setup" />
    <x-card><form method="POST" action="{{ route('grades.store') }}" data-loading>@csrf
        @include('grades.partials.form', ['grade' => null])
        <div class="btn-row"><button type="submit" class="btn">Create grade</button><a class="btn btn-secondary" href="{{ route('grades.index') }}">Cancel</a></div>
    </form></x-card>
@endsection
