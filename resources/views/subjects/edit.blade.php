@extends('layouts.app')

@section('title', 'Edit '.$subject->name)

@section('content')
    <x-page-header :title="'Edit '.$subject->name" subtitle="Update this subject's code or name." eyebrow="Academic setup" />
    <x-card><form method="POST" action="{{ route('subjects.update', $subject) }}" data-loading>@csrf @method('PUT') @include('subjects.partials.form')<div class="btn-row"><button type="submit" class="btn">Save subject</button><a class="btn btn-secondary" href="{{ route('subjects.show', $subject) }}">Cancel</a></div></form></x-card>
@endsection
