@extends('layouts.app')

@section('title', 'Payment reversals')

@section('content')
    <x-page-header title="Payment reversals" subtitle="Append-only correction requests and approvals." eyebrow="Payments" />

    <div class="table-wrap"><table class="table"><thead><tr><th>Payment</th><th>Family</th><th>Requested by</th><th>Status</th><th>Correction receipt</th></tr></thead><tbody>
        @forelse ($paymentReversals as $paymentReversal)
            <tr><td><a href="{{ route('payment-reversals.show', $paymentReversal) }}">Payment {{ $paymentReversal->originalPayment->id }}</a></td><td>{{ $paymentReversal->originalPayment->family->family_code }}</td><td>{{ $paymentReversal->requestedBy->name }}</td><td>{{ $paymentReversal->status }}</td><td>{{ $paymentReversal->correctionReceipt?->receipt_no }}</td></tr>
        @empty
            <tr class="table-empty"><td colspan="5"><span class="empty-state-title">No payment reversals</span>Accountants can request a reversal from a payment record.</td></tr>
        @endforelse
    </tbody></table></div>
    <x-pagination :paginator="$paymentReversals" />
@endsection
