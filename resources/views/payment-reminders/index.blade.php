@extends('layouts.app')

@section('title', 'Payment reminders')

@section('content')
    <x-page-header title="Payment reminders"
                   subtitle="Internal records of which guardians need telling about balances." />

    <div class="page-actions">
        <x-button-link :href="route('payment-reminders.create')">Generate reminder records</x-button-link>
    </div>

    <x-card title="Filters">
        <form method="GET" action="{{ route('payment-reminders.index') }}">
            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="family_id">Family</label>
                    <select class="form-control" id="family_id" name="family_id">
                        <option value="">-- all --</option>
                        @foreach ($families as $family)
                            <option value="{{ $family->id }}" {{ (int) request('family_id') === $family->id ? 'selected' : '' }}>
                                {{ $family->family_code }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="guardian_id">Guardian</label>
                    <select class="form-control" id="guardian_id" name="guardian_id">
                        <option value="">-- all --</option>
                        @foreach ($guardians as $guardian)
                            <option value="{{ $guardian->id }}" {{ (int) request('guardian_id') === $guardian->id ? 'selected' : '' }}>
                                {{ $guardian->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="reminder_type">Type</label>
                    <select class="form-control" id="reminder_type" name="reminder_type">
                        <option value="">-- all --</option>
                        <option value="upcoming" {{ request('reminder_type') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        <option value="overdue" {{ request('reminder_type') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">-- all --</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn">Apply filters</button>
            </div>
        </form>
    </x-card>

    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>Family</th><th>Guardian</th><th>Type</th><th>Status</th>
                <th class="num">Due items</th><th>Scheduled for</th><th class="actions">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($reminders as $reminder)
                <tr>
                    <td>{{ $reminder->family->family_code }}</td>
                    <td>{{ $reminder->guardian->name }}</td>
                    <td><x-status-badge :value="$reminder->reminder_type" /></td>
                    <td><x-status-badge :value="$reminder->status" /></td>
                    <td class="num">{{ count($reminder->due_item_ids) }}</td>
                    <td>{{ $reminder->scheduled_for?->toDateString() }}</td>
                    <td class="actions">
                        <x-button-link :href="route('payment-reminders.show', $reminder)" variant="quiet" size="small">View</x-button-link>
                    </td>
                </tr>
            @empty
                <tr class="table-empty">
                    <td colspan="7">
                        <span class="empty-state-title">No reminder records yet</span>
                        Generate reminders for guardians linked to students with outstanding balances.
                        <div class="empty-actions">
                            <x-button-link :href="route('payment-reminders.create')" size="small">Generate reminders</x-button-link>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <p class="note">These are internal reminder records only. Nothing is sent by email, SMS, or WhatsApp.</p>
@endsection
