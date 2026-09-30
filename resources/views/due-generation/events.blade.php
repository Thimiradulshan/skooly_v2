@extends('layouts.app')

@section('title', 'Event due generation')
@section('heading', 'Event due generation')

@section('content')
    @if ($events->isEmpty())
        <p>No events exist yet. Events must be created before their dues can be generated.</p>
    @else
        <form method="POST" action="{{ route('due-generation.events.store') }}">
            @csrf

            <label for="event_id">Event</label>
            <select id="event_id" name="event_id" required>
                <option value="">-- choose --</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}"
                        {{ (int) old('event_id') === $event->id ? 'selected' : '' }}>
                        {{ $event->name }} ({{ $event->event_date->toDateString() }})
                    </option>
                @endforeach
            </select>

            <p>
                <button type="submit">Generate event dues</button>
                <a href="{{ route('dues-dashboard.index') }}">View dashboard</a>
            </p>
        </form>
    @endif

    <p>
        This page only generates dues for existing events. Event creation and editing are not part of this phase.
    </p>
@endsection
