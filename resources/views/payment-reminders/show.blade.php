@extends('layouts.app')

@section('title', 'Payment reminder '.$paymentReminder->id)
@section('heading', 'Payment reminder '.$paymentReminder->id)

@section('content')
    <p><a href="{{ route('payment-reminders.index') }}">Back to payment reminders</a></p>

    <table>
        <tbody>
        <tr><th>Family</th><td>{{ $paymentReminder->family->family_code }}</td></tr>
        <tr><th>Guardian</th><td>{{ $paymentReminder->guardian->name }}</td></tr>
        <tr><th>Reminder type</th><td>{{ $paymentReminder->reminder_type }}</td></tr>
        <tr><th>Status</th><td>{{ $paymentReminder->status }}</td></tr>
        <tr><th>Scheduled for</th><td>{{ $paymentReminder->scheduled_for?->toDateString() }}</td></tr>
        <tr><th>Sent at</th><td>{{ $paymentReminder->sent_at?->toDateTimeString() }}</td></tr>
        <tr><th>Due item IDs</th><td>{{ implode(', ', $paymentReminder->due_item_ids) }}</td></tr>
        </tbody>
    </table>

    <h2>Stored message snapshot</h2>
    <table>
        <tbody>
        <tr><th>Family</th><td>{{ $paymentReminder->message_snapshot['family_code'] ?? '' }}</td></tr>
        <tr><th>Guardian</th><td>{{ $paymentReminder->message_snapshot['guardian_name'] ?? '' }}</td></tr>
        <tr><th>Type</th><td>{{ $paymentReminder->message_snapshot['reminder_type'] ?? '' }}</td></tr>
        <tr><th>As of date</th><td>{{ $paymentReminder->message_snapshot['as_of_date'] ?? '' }}</td></tr>
        <tr><th>Total balance</th><td>{{ $paymentReminder->message_snapshot['total_balance_amount'] ?? '' }}</td></tr>
        <tr><th>Due item count</th><td>{{ $paymentReminder->message_snapshot['due_item_count'] ?? '' }}</td></tr>
        </tbody>
    </table>

    <h2>Stored due item snapshot</h2>
    <table>
        <thead><tr><th>Student</th><th>Admission no.</th><th>Description</th><th>Due date</th><th>Balance</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($paymentReminder->message_snapshot['due_items'] ?? [] as $dueItem)
            <tr>
                <td>{{ $dueItem['student_name'] }}</td>
                <td>{{ $dueItem['admission_no'] }}</td>
                <td>{{ $dueItem['description'] }}</td>
                <td>{{ $dueItem['due_date'] }}</td>
                <td>{{ $dueItem['balance_amount'] }}</td>
                <td>{{ $dueItem['status'] }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No stored due item snapshot.</td></tr>
        @endforelse
        </tbody>
    </table>

    <p>This page displays stored reminder data only. No message is sent from this page.</p>
@endsection
