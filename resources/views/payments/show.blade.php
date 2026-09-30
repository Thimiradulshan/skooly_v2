@extends('layouts.app')

@section('title', 'Payment '.$payment->id)
@section('heading', 'Payment '.$payment->id)

@section('content')
    <p><a href="{{ route('families.show', $payment->family) }}">Back to family</a></p>

    <table>
        <tbody>
        <tr><th>Family</th><td>{{ $payment->family->family_code }}</td></tr>
        <tr><th>Payment reference</th><td>{{ $payment->payment_reference }}</td></tr>
        <tr><th>Method</th><td>{{ $payment->method }}</td></tr>
        <tr><th>Paid at</th><td>{{ $payment->paid_at->toDateString() }}</td></tr>
        <tr><th>Amount</th><td>{{ $payment->amount }}</td></tr>
        <tr><th>Notes</th><td>{{ $payment->notes }}</td></tr>
        </tbody>
    </table>

    <h2>Allocations</h2>
    <table>
        <thead><tr><th>Student</th><th>Description</th><th>Allocated</th></tr></thead>
        <tbody>
        @foreach ($payment->allocations as $allocation)
            <tr>
                <td>{{ $allocation->studentDueItem->student->name }}</td>
                <td>{{ $allocation->studentDueItem->description }}</td>
                <td>{{ $allocation->amount }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if ($payment->receipt)
        <p><a href="{{ route('receipts.show', $payment->receipt) }}">View receipt {{ $payment->receipt->receipt_no }}</a></p>
    @endif
@endsection
