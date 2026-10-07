@extends('layouts.app')

@section('title', 'Request payment reversal')

@section('content')
    <x-page-header title="Request payment reversal" subtitle="This request does not change the original payment, receipt, or allocations." eyebrow="Payments" />
    <x-card title="Payment {{ $payment->id }}">
        <dl class="kv"><div class="kv-row"><dt>Family</dt><dd>{{ $payment->family->family_code }}</dd></div><div class="kv-row"><dt>Amount</dt><dd>{{ $payment->amount }}</dd></div></dl>
    </x-card>
    <x-card title="Reason">
        <form method="POST" action="{{ route('payments.reversals.store', $payment) }}" data-loading>
            @csrf
            <div class="form-field"><label class="form-label" for="reason">Reason</label><textarea class="form-control" id="reason" name="reason" required>{{ old('reason') }}</textarea></div>
            <div class="btn-row"><button class="btn" type="submit">Request reversal</button></div>
        </form>
    </x-card>
@endsection
