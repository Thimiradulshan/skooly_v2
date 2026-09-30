@extends('layouts.app')

@section('title', 'Create term')

@section('content')
    <x-page-header title="Create term" subtitle="Add a teaching term to an academic year." eyebrow="Academic setup" />
    <x-card><form method="POST" action="{{ route('terms.store') }}" data-loading>@csrf
        @include('terms.partials.form', ['term' => null])
        <div class="btn-row"><button type="submit" class="btn">Create term</button><a class="btn btn-secondary" href="{{ route('terms.index') }}">Cancel</a></div>
    </form></x-card>
@endsection
