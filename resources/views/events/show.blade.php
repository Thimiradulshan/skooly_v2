@extends('layouts.app')

@section('title', $event->name)

@section('content')
    <x-page-header :title="$event->name" subtitle="Event details, charges, and participation." />

    <div class="page-actions">
        <x-button-link :href="route('events.edit', $event)" variant="secondary">Edit event</x-button-link>
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
                <thead><tr><th>Grade</th><th class="num">Amount</th></tr></thead>
                <tbody>
                @forelse ($event->charges as $charge)
                    <tr>
                        <td>{{ $charge->grade->name }}</td>
                        <td class="num">{{ $charge->amount }}</td>
                    </tr>
                @empty
                    <tr class="table-empty"><td colspan="2">No charges yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="note">Charges cannot be edited or deleted once added, because they may already have produced due items.</p>
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
@endsection
