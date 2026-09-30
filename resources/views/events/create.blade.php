@extends('layouts.app')

@section('title', 'Create event')

@section('content')
    <x-page-header title="Create event"
                   subtitle="Events hold per-grade charges. Creating one does not generate dues." />

    <x-card>
        @include('events.partials.form', ['action' => route('events.store'), 'event' => null, 'method' => 'POST'])
    </x-card>
@endsection
