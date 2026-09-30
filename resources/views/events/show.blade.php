@extends('layouts.app')

@section('title', $event->name)
@section('heading', $event->name)

@section('content')
    <p><a href="{{ route('events.index') }}">Back to events</a></p>
    <p><a href="{{ route('events.edit', $event) }}">Edit event</a></p>
    <p><a href="{{ route('events.charges.create', $event) }}">Add charge</a></p>
    <p><a href="{{ route('events.participation.create', $event) }}">Manage participation</a></p>
    <p><a href="{{ route('due-generation.events.create') }}">Generate event dues</a></p>

    <table>
        <tbody>
        <tr><th>Academic year</th><td>{{ $event->academicYear->name }}</td></tr>
        <tr><th>Fee category</th><td>{{ $event->feeCategory->name }}</td></tr>
        <tr><th>Date</th><td>{{ $event->event_date->toDateString() }}</td></tr>
        <tr><th>Description</th><td>{{ $event->description }}</td></tr>
        <tr><th>Mandatory</th><td>{{ $event->is_mandatory ? 'Yes' : 'No' }}</td></tr>
        <tr><th>Confirmed at</th><td>{{ $event->confirmed_at?->toDateTimeString() }}</td></tr>
        <tr><th>Generated dues</th><td>{{ $event->event_due_items_count }}</td></tr>
        </tbody>
    </table>

    <h2>Charges</h2>
    <table>
        <thead><tr><th>Grade</th><th>Amount</th></tr></thead>
        <tbody>
        @forelse ($event->charges as $charge)
            <tr><td>{{ $charge->grade->name }}</td><td>{{ $charge->amount }}</td></tr>
        @empty
            <tr><td colspan="2">No charges yet.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>Participation</h2>
    <table>
        <thead><tr><th>Student</th><th>Admission no.</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($event->participations as $participation)
            <tr>
                <td>{{ $participation->student->name }}</td>
                <td>{{ $participation->student->admission_no }}</td>
                <td>{{ $participation->status }}</td>
            </tr>
        @empty
            <tr><td colspan="3">No participation records yet.</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
