@extends('layouts.app')

@section('title', 'Event due generation')

@section('content')
    <x-page-header title="Event due generation"
                   subtitle="Charge the applicable students for an existing event." />

    @if ($events->isEmpty())
        <x-empty-state title="No events exist yet"
                       description="Events must be created, with at least one charge, before their dues can be generated." />

        <div class="page-actions">
            <x-button-link :href="route('events.index')">Go to Events</x-button-link>
        </div>
    @else
        <x-card>
            <form method="POST" action="{{ route('due-generation.events.store') }}">
                @csrf

                <div class="form-field">
                    <label class="form-label" for="event_id">Event <span class="req">*</span></label>
                    <select class="form-control" id="event_id" name="event_id" required>
                        <option value="">-- choose --</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}" {{ (int) old('event_id') === $event->id ? 'selected' : '' }}>
                                {{ $event->name }} ({{ $event->event_date->toDateString() }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn">Generate event dues</button>
                    <x-button-link :href="route('dues-dashboard.index')" variant="secondary">View dashboard</x-button-link>
                </div>
            </form>
        </x-card>
    @endif

    <p class="note">This page only generates dues for events that already exist. Event creation and editing are not part of this page.</p>
@endsection
