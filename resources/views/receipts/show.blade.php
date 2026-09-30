@extends('layouts.app')

@section('title', 'Receipt '.$receipt->receipt_no)
@section('heading', 'Receipt '.$receipt->receipt_no)

@section('content')
    <p><a href="{{ route('payments.show', $receipt->payment_id) }}">Back to payment</a></p>

    <table>
        <tbody>
        <tr><th>Receipt number</th><td>{{ $receipt->receipt_no }}</td></tr>
        <tr><th>Issued at</th><td>{{ $receipt->issued_at->toDateString() }}</td></tr>
        <tr><th>Total amount</th><td>{{ $receipt->total_amount }}</td></tr>
        <tr><th>Family</th><td>{{ $receipt->family_snapshot['family_code'] ?? '' }}</td></tr>
        <tr><th>Method</th><td>{{ $receipt->payment_snapshot['method'] ?? '' }}</td></tr>
        <tr><th>Payment amount</th><td>{{ $receipt->payment_snapshot['amount'] ?? '' }}</td></tr>
        </tbody>
    </table>

    <h2>Allocation snapshot</h2>
    <table>
        <thead><tr><th>Description</th><th>Original</th><th>Discount</th><th>Allocated</th><th>Balance after</th></tr></thead>
        <tbody>
        @foreach ($receipt->allocation_snapshot as $allocation)
            <tr>
                <td>{{ $allocation['description'] }}</td>
                <td>{{ $allocation['original_amount'] }}</td>
                <td>{{ $allocation['discount_amount'] }}</td>
                <td>{{ $allocation['allocation_amount'] }}</td>
                <td>{{ $allocation['balance_amount'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <p>This page displays stored receipt snapshots and does not recalculate from live due items.</p>
@endsection
