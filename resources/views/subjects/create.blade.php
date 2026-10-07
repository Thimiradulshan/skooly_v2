@extends('layouts.app')

@section('title', 'Create subject')

@section('content')
    <x-page-header title="Create subject" subtitle="Add a subject that teachers can be qualified and assigned to teach." eyebrow="Academic setup" />
    <x-card><form method="POST" action="{{ route('subjects.store') }}" data-loading>@csrf @include('subjects.partials.form')<div class="btn-row"><button type="submit" class="btn">Create subject</button><a class="btn btn-secondary" href="{{ route('subjects.index') }}">Cancel</a></div></form></x-card>
@endsection
