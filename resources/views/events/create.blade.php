@extends('layouts.app')

@section('title', 'Create event')
@section('heading', 'Create event')

@section('content')
    @include('events.partials.form', ['action' => route('events.store'), 'event' => null, 'method' => 'POST'])
@endsection
