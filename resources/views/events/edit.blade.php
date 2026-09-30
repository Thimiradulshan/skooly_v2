@extends('layouts.app')

@section('title', 'Edit '.$event->name)

@section('content')
    <x-page-header :title="'Edit '.$event->name" subtitle="Update the event details." />

    <x-card>
        @include('events.partials.form', ['action' => route('events.update', $event), 'event' => $event, 'method' => 'PUT'])
    </x-card>
@endsection
