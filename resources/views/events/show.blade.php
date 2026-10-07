@extends('layouts.app')

@section('title', $event->name)

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('events.index') }}">Events</a>
        <span class="breadcrumb-sep">/</span>
        <span>{{ $event->name }}</span>
    </div>

    <x-page-header :title="$event->name" subtitle="Event details, charges, and participation." eyebrow="School life" />

    <div class="page-actions">
        <x-button-link :href="route('events.edit', $event)">Edit event</x-button-link>
        <x-button-link :href="route('events.charges.create', $event)" variant="secondary">Add charge</x-button-link>
        <x-button-link :href="route('events.participation.create', $event)" variant="secondary">Manage participation</x-button-link>
        <x-button-link :href="route('due-generation.events.create')" variant="secondary">Generate event dues</x-button-link>
    </div>

    <x-card title="Details">
        <dl class="kv">
            <div class="kv-row"><dt>Academic year</dt><dd>{{ $event->academicYear->name }}</dd></div>
            <div class="kv-row"><dt>Fee category</dt><dd>{{ $event->feeCategory->name }}</dd></div>
            <div class="kv-row"><dt>Date</dt><dd>{{ $event->event_date->toDateString() }}</dd></div>
            <div class="kv-row"><dt>Description</dt><dd>{{ $event->description }}</dd></div>
            <div class="kv-row"><dt>Mandatory</dt><dd>{{ $event->is_mandatory ? 'Yes' : 'No' }}</dd></div>
            <div class="kv-row"><dt>Confirmed at</dt><dd>{{ $event->confirmed_at?->toDateTimeString() }}</dd></div>
            <div class="kv-row"><dt>Generated dues</dt><dd>{{ $event->event_due_items_count }}</dd></div>
        </dl>
    </x-card>

        <x-card title="Charges">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Grade</th><th class="num">Amount</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($event->charges as $charge)
                    <tr>
                        <td>{{ $charge->grade->name }}</td>
                        <td class="num">{{ $charge->amount }}</td>
                        <td>{{ $event->event_due_items_count > 0 ? 'Price locked' : 'Editable' }}</td>
                        <td>
                            @if ($event->event_due_items_count === 0)
                                <a class="table-action" href="{{ route('events.charges.edit', [$event, $charge]) }}">Edit</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr class="table-empty">
                        <td colspan="4">
                            <span class="empty-state-title">No charges yet</span>
                            Add a charge so the event can generate dues.
                            <div class="empty-actions">
                                <x-button-link :href="route('events.charges.create', $event)" size="small">Add charge</x-button-link>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="note">Amounts can be edited until this event generates a due item. Grade is fixed, and every charge locks once any event due exists. Generated due item snapshots are never changed.</p>
    </x-card>

    <x-card title="Participation">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Student</th><th>Admission no.</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($event->participations as $participation)
                    <tr>
                        <td>{{ $participation->student->name }}</td>
                        <td>{{ $participation->student->admission_no }}</td>
                        <td><x-status-badge :value="$participation->status" /></td>
                    </tr>
                @empty
                    <tr class="table-empty"><td colspan="3">No participation records yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="note">Participation only affects opt-in events. Mandatory events charge every enrolled student.</p>
    </x-card>

    <div class="callout">
        <span class="callout-mark" aria-hidden="true">i</span>
        <p>Dues for this event are only created from the event due generation page. Editing an event never creates or changes due items.</p>
    </div>
@endsection
