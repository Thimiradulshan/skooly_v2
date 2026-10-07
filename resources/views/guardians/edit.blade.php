@extends('layouts.app')
@section('title', 'Edit '.$guardian->name)
@section('content')
    <x-page-header :title="'Edit '.$guardian->name" subtitle="Update this guardian's registration details." eyebrow="Registration" />
    <x-card><form method="POST" action="{{ route('guardians.update', $guardian) }}" data-loading>@csrf @method('PUT') @include('guardians.partials.form')<div class="btn-row"><button type="submit" class="btn">Save guardian</button><a class="btn btn-secondary" href="{{ route('guardians.show', $guardian) }}">Cancel</a></div></form></x-card>
@endsection
