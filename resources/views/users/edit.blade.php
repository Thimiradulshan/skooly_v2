@extends('layouts.app')

@section('title', 'Edit '.$user->name)

@section('content')
    <x-page-header :title="'Edit '.$user->name" subtitle="Update this staff account, its roles, password, or active status." eyebrow="Staff" />
    <x-card><form method="POST" action="{{ route('users.update', $user) }}" data-loading>@csrf @method('PUT') @include('users.partials.form')<div class="btn-row"><button type="submit" class="btn">Save user</button><a class="btn btn-secondary" href="{{ route('users.show', $user) }}">Cancel</a></div></form></x-card>
@endsection
