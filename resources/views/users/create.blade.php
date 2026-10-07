@extends('layouts.app')

@section('title', 'Create user')

@section('content')
    <x-page-header title="Create user" subtitle="Add a staff account and assign its fixed role." eyebrow="Staff" />
    <x-card><form method="POST" action="{{ route('users.store') }}" data-loading>@csrf @include('users.partials.form')<div class="btn-row"><button type="submit" class="btn">Create user</button><a class="btn btn-secondary" href="{{ route('users.index') }}">Cancel</a></div></form></x-card>
@endsection
