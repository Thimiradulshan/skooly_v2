@extends('layouts.app')

@section('title', 'Payment reminders')
@section('heading', 'Payment reminders')

@section('content')
    <p><a href="{{ route('payment-reminders.create') }}">Generate reminder records</a></p>

    <form method="GET" action="{{ route('payment-reminders.index') }}">
        <label for="family_id">Family</label>
        <select id="family_id" name="family_id">
            <option value="">-- all --</option>
            @foreach ($families as $family)
                <option value="{{ $family->id }}" {{ (int) request('family_id') === $family->id ? 'selected' : '' }}>
                    {{ $family->family_code }}
                </option>
            @endforeach
        </select>

        <label for="guardian_id">Guardian</label>
        <select id="guardian_id" name="guardian_id">
            <option value="">-- all --</option>
            @foreach ($guardians as $guardian)
                <option value="{{ $guardian->id }}" {{ (int) request('guardian_id') === $guardian->id ? 'selected' : '' }}>
                    {{ $guardian->name }}
                </option>
            @endforeach
        </select>

        <label for="reminder_type">Type</label>
        <select id="reminder_type" name="reminder_type">
            <option value="">-- all --</option>
            <option value="upcoming" {{ request('reminder_type') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
            <option value="overdue" {{ request('reminder_type') === 'overdue' ? 'selected' : '' }}>Overdue</option>
        </select>

        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">-- all --</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>

        <p><button type="submit">Apply filters</button></p>
    </form>

    <table>
        <thead>
        <tr><th>Family</th><th>Guardian</th><th>Type</th><th>Status</th><th>Due items</th><th>Scheduled for</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($reminders as $reminder)
            <tr>
                <td>{{ $reminder->family->family_code }}</td>
                <td>{{ $reminder->guardian->name }}</td>
                <td>{{ $reminder->reminder_type }}</td>
                <td>{{ $reminder->status }}</td>
                <td>{{ count($reminder->due_item_ids) }}</td>
                <td>{{ $reminder->scheduled_for?->toDateString() }}</td>
                <td><a href="{{ route('payment-reminders.show', $reminder) }}">View</a></td>
            </tr>
        @empty
            <tr><td colspan="7">No reminder records yet.</td></tr>
        @endforelse
        </tbody>
    </table>

    <p>These are internal reminder records only. This page does not send SMS, WhatsApp, or email.</p>
@endsection
