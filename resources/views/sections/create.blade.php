@extends('layouts.app')

@section('title', 'Create section')

@section('content')
    <x-page-header title="Create section" subtitle="Add a class section to an existing grade." eyebrow="Academic setup" />
    <x-card><form method="POST" action="{{ route('sections.store') }}" data-loading>@csrf
        @include('sections.partials.form', ['section' => null])
        <div class="btn-row"><button type="submit" class="btn">Create section</button><a class="btn btn-secondary" href="{{ route('sections.index') }}">Cancel</a></div>
    </form></x-card>
@endsection
