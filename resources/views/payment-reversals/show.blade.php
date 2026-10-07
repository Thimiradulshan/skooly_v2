@extends('layouts.app')

@section('title', 'Payment reversal '.$paymentReversal->id)

@section('content')
    <x-page-header :title="'Payment reversal '.$paymentReversal->id" subtitle="Original financial records remain unchanged; approval reopens their due items." eyebrow="Payments" />
    <x-card title="Request">
        <dl class="kv">
            <div class="kv-row"><dt>Original payment</dt><dd><a href="{{ route('payments.show', $paymentReversal->originalPayment) }}">Payment {{ $paymentReversal->originalPayment->id }}</a></dd></div>
            <div class="kv-row"><dt>Requested by</dt><dd>{{ $paymentReversal->requestedBy->name }}</dd></div>
            <div class="kv-row"><dt>Reason</dt><dd>{{ $paymentReversal->reason }}</dd></div>
            <div class="kv-row"><dt>Status</dt><dd>{{ $paymentReversal->status }}</dd></div>
            @if ($paymentReversal->approvedBy)<div class="kv-row"><dt>Approved by</dt><dd>{{ $paymentReversal->approvedBy->name }}</dd></div>@endif
            @if ($paymentReversal->correctionReceipt)<div class="kv-row"><dt>Correction receipt</dt><dd>{{ $paymentReversal->correctionReceipt->receipt_no }}</dd></div>@endif
        </dl>
    </x-card>

    @if ($paymentReversal->status === \App\Models\PaymentReversal::STATUS_REQUESTED && auth()->user()->can('approve', $paymentReversal))
        <x-card title="Approve reversal">
            <form method="POST" action="{{ route('payment-reversals.approve', $paymentReversal) }}" data-confirm="Approve this full payment reversal? The original records remain immutable and each allocation will be reopened." data-loading>
                @csrf
                <div class="form-field"><label class="form-label" for="receipt_no">Correction receipt number</label><input class="form-control" id="receipt_no" name="receipt_no" value="{{ old('receipt_no') }}" required></div>
                <div class="btn-row"><button class="btn" type="submit">Approve reversal</button></div>
            </form>
        </x-card>
    @endif
@endsection
