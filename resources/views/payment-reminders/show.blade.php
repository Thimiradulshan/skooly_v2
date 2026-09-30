@extends('layouts.app')

@section('title', 'Payment reminder '.$paymentReminder->id)

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('payment-reminders.index') }}">Payment reminders</a>
        <span class="breadcrumb-sep">/</span>
        <span>Reminder {{ $paymentReminder->id }}</span>
    </div>

    <x-page-header :title="'Payment reminder '.$paymentReminder->id"
                   subtitle="Stored preview of what would be sent to this guardian."
                   eyebrow="Communication" />

    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('payment-reminders.index') }}">Back to payment reminders</a>
    </div>

    <div class="callout" data-testid="reminder-not-sent">
        <span class="callout-mark" aria-hidden="true">i</span>
        <p><strong>Preview only.</strong> This is an internal outbox record. Nothing has been sent, no email, SMS, or WhatsApp provider is connected, and this record is never marked as sent.</p>
    </div>

    <x-card title="Reminder">
        <dl class="kv">
            <div class="kv-row"><dt>Family</dt><dd>{{ $paymentReminder->family->family_code }}</dd></div>
            <div class="kv-row"><dt>Guardian</dt><dd>{{ $paymentReminder->guardian->name }}</dd></div>
            <div class="kv-row"><dt>Reminder type</dt><dd><x-status-badge :value="$paymentReminder->reminder_type" /></dd></div>
            <div class="kv-row"><dt>Status</dt><dd><x-status-badge :value="$paymentReminder->status" /></dd></div>
            <div class="kv-row"><dt>Scheduled for</dt><dd>{{ $paymentReminder->scheduled_for?->toDateString() }}</dd></div>
            <div class="kv-row"><dt>Sent at</dt><dd>{{ $paymentReminder->sent_at?->toDateTimeString() }}</dd></div>
            <div class="kv-row"><dt>Due item IDs</dt><dd>{{ implode(', ', $paymentReminder->due_item_ids) }}</dd></div>
        </dl>
    </x-card>

    <x-card title="Stored message snapshot">
        <dl class="kv">
            <div class="kv-row"><dt>Family</dt><dd>{{ $paymentReminder->message_snapshot['family_code'] ?? '' }}</dd></div>
            <div class="kv-row"><dt>Guardian</dt><dd>{{ $paymentReminder->message_snapshot['guardian_name'] ?? '' }}</dd></div>
            <div class="kv-row"><dt>Type</dt><dd>{{ $paymentReminder->message_snapshot['reminder_type'] ?? '' }}</dd></div>
            <div class="kv-row"><dt>As of date</dt><dd>{{ $paymentReminder->message_snapshot['as_of_date'] ?? '' }}</dd></div>
            <div class="kv-row"><dt>Total balance</dt><dd>{{ $paymentReminder->message_snapshot['total_balance_amount'] ?? '' }}</dd></div>
            <div class="kv-row"><dt>Due item count</dt><dd>{{ $paymentReminder->message_snapshot['due_item_count'] ?? '' }}</dd></div>
        </dl>
    </x-card>

    <x-card title="Stored due item snapshot">
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr><th>Student</th><th>Admission no.</th><th>Description</th><th>Due date</th><th class="num">Balance</th><th>Status</th></tr>
                </thead>
                <tbody>
                @forelse ($paymentReminder->message_snapshot['due_items'] ?? [] as $dueItem)
                    <tr>
                        <td>{{ $dueItem['student_name'] }}</td>
                        <td>{{ $dueItem['admission_no'] }}</td>
                        <td>{{ $dueItem['description'] }}</td>
                        <td>{{ $dueItem['due_date'] }}</td>
                        <td class="num">{{ $dueItem['balance_amount'] }}</td>
                        <td><x-status-badge :value="$dueItem['status']" /></td>
                    </tr>
                @empty
                    <tr class="table-empty"><td colspan="6">No stored due item snapshot.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <p class="note">This page displays stored reminder data only. No message is sent from here.</p>
@endsection
