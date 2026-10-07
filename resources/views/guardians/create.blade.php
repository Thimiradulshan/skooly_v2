@extends('layouts.app')
@section('title', 'Add guardian')
@section('content')
    <x-page-header title="Add guardian" :subtitle="'Add a guardian to '.$family->family_code.'. Student visibility is granted separately.'" eyebrow="Registration" />
    <x-card><form method="POST" action="{{ route('families.guardians.store', $family) }}" data-loading>@csrf @include('guardians.partials.form')<div class="btn-row"><button type="submit" class="btn">Add guardian</button><a class="btn btn-secondary" href="{{ route('families.show', $family) }}">Cancel</a></div></form></x-card>
@endsection
