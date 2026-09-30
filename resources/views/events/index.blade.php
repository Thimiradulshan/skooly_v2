@extends('layouts.app')

@section('title', 'Events')

@section('content')
    <x-page-header title="Events"
                   subtitle="School events with per-grade charges and participation." />

    <div class="page-actions">
        <x-button-link :href="route('events.create')">Create event</x-button-link>
        <x-button-link :href="route('due-generation.events.create')" variant="secondary">Event due generation</x-button-link>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>Name</th><th>Date</th><th>Academic year</th><th>Fee category</th>
                <th>Mandatory</th><th class="num">Charges</th><th class="num">Participants</th><th class="num">Dues</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($events as $event)
                <tr>
                    <td><a href="{{ route('events.show', $event) }}">{{ $event->name }}</a></td>
                    <td>{{ $event->event_date->toDateString() }}</td>
                    <td>{{ $event->academicYear->name }}</td>
                    <td>{{ $event->feeCategory->name }}</td>
                    <td>{{ $event->is_mandatory ? 'Yes' : 'No' }}</td>
                    <td class="num">{{ $event->charges_count }}</td>
                    <td class="num">{{ $event->participations_count }}</td>
                    <td class="num">{{ $event->event_due_items_count }}</td>
                </tr>
            @empty
                <tr class="table-empty"><td colspan="8">No events yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
