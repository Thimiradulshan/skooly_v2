@extends('layouts.app')

@section('title', 'Event due generation')

@section('content')
    <x-page-header title="Event due generation"
                   subtitle="Charge the applicable students for an existing event."
                   eyebrow="Fees and dues" />

    @if ($events->isEmpty())
        <x-empty-state title="No events exist yet"
                       description="Events must be created, with at least one charge, before their dues can be generated." />

        <div class="page-actions">
            <x-button-link :href="route('events.index')">Go to Events</x-button-link>
        </div>
    @else
        <x-card>
            <form method="POST" action="{{ route('due-generation.events.store') }}"
                  data-confirm="Generate due items for the selected event? Running this twice will not duplicate anything."
                  data-confirm-title="Generate event dues"
                  data-confirm-action="Generate dues"
                  data-loading>
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
                    <span class="form-help">Mandatory events charge every enrolled student. Opt-in events charge only opted-in students.</span>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn">Generate event dues</button>
                    <x-button-link :href="route('events.index')" variant="secondary">Cancel</x-button-link>
                </div>
            </form>
        </x-card>
    @endif

    <div class="page-actions">
        <x-button-link :href="route('dues-dashboard.index')" variant="secondary">Back to dues dashboard</x-button-link>
    </div>

    <p class="note">This page only generates dues for events that already exist. Event creation and editing are not part of this page.</p>
@endsection
