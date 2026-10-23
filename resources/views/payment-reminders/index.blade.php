@extends('layouts.app')

@section('title', 'Payment reminders')

@section('content')
    <x-page-header title="Payment reminders"
                   subtitle="Internal records of which guardians need telling about balances." />

    @if (auth()->user()->hasRole(\App\Models\Role::ADMIN))
        <div class="page-actions">
            <x-button-link :href="route('payment-reminders.create')">Generate reminder records</x-button-link>
        </div>
    @endif

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
                <div class="form-field"><label class="form-label" for="delivery_status">Latest delivery</label><select class="form-control" id="delivery_status" name="delivery_status"><option value="">-- all --</option><option value="queued" {{ request('delivery_status') === 'queued' ? 'selected' : '' }}>Queued</option><option value="transport_accepted" {{ request('delivery_status') === 'transport_accepted' ? 'selected' : '' }}>Transport accepted</option><option value="failed" {{ request('delivery_status') === 'failed' ? 'selected' : '' }}>Failed</option></select></div>

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
                <th class="num">Due items</th><th>Latest delivery attempt</th><th>Latest timestamp</th><th class="actions">Actions</th>
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
                    <td>@if ($reminder->latestDeliveryAttempt)<x-status-badge :value="$reminder->latestDeliveryAttempt->status" />@else No attempt @endif</td>
                    <td>{{ $reminder->latestDeliveryAttempt?->transport_accepted_at?->toDateTimeString() ?? $reminder->latestDeliveryAttempt?->failed_at?->toDateTimeString() ?? $reminder->latestDeliveryAttempt?->created_at?->toDateTimeString() }}</td>
                    <td class="actions">
                        <x-button-link :href="route('payment-reminders.show', $reminder)" variant="quiet" size="small">View</x-button-link>
                    </td>
                </tr>
            @empty
                <tr class="table-empty">
                    <td colspan="8">
                        <span class="empty-state-title">No reminder records yet</span>
                        Generate reminders for guardians linked to students with outstanding balances.
                        @if (auth()->user()->hasRole(\App\Models\Role::ADMIN))
                            <div class="empty-actions">
                                <x-button-link :href="route('payment-reminders.create')" size="small">Generate reminders</x-button-link>
                            </div>
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <x-pagination :paginator="$reminders" />
    <p class="note">Reminder status and delivery-attempt status are separate. Queued is not sent; transport accepted is not inbox delivery; failed does not settle or cancel a due item.</p>
@endsection
