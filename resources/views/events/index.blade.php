@extends('layouts.app')

@section('title', 'Events')
@section('heading', 'Events')

@section('content')
    <p><a href="{{ route('events.create') }}">Create event</a></p>

    <table>
        <thead>
        <tr><th>Name</th><th>Date</th><th>Academic year</th><th>Fee category</th><th>Mandatory</th><th>Charges</th><th>Participants</th><th>Dues</th></tr>
        </thead>
        <tbody>
        @forelse ($events as $event)
            <tr>
                <td><a href="{{ route('events.show', $event) }}">{{ $event->name }}</a></td>
                <td>{{ $event->event_date->toDateString() }}</td>
                <td>{{ $event->academicYear->name }}</td>
                <td>{{ $event->feeCategory->name }}</td>
                <td>{{ $event->is_mandatory ? 'Yes' : 'No' }}</td>
                <td>{{ $event->charges_count }}</td>
                <td>{{ $event->participations_count }}</td>
                <td>{{ $event->event_due_items_count }}</td>
            </tr>
        @empty
            <tr><td colspan="8">No events yet.</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
