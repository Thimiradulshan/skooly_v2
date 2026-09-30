@extends('layouts.app')

@section('title', 'Edit '.$event->name)
@section('heading', 'Edit event')

@section('content')
    @include('events.partials.form', ['action' => route('events.update', $event), 'event' => $event, 'method' => 'PUT'])
@endsection
