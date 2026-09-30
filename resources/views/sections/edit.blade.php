@extends('layouts.app')

@section('title', 'Edit '.$section->name)

@section('content')
    <x-page-header :title="'Edit '.$section->name" subtitle="Update this section's grade, name, or capacity." eyebrow="Academic setup" />
    <x-card><form method="POST" action="{{ route('sections.update', $section) }}" data-loading>@csrf @method('PUT')
        @include('sections.partials.form', ['section' => $section])
        <div class="btn-row"><button type="submit" class="btn">Save section</button><a class="btn btn-secondary" href="{{ route('sections.show', $section) }}">Cancel</a></div>
    </form></x-card>
@endsection
