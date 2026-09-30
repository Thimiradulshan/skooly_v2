@extends('layouts.app')

@section('title', 'Payment '.$payment->id)

@section('content')
    <x-page-header :title="'Payment '.$payment->id" subtitle="Recorded payment and its allocations." />

    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('families.show', $payment->family) }}">Back to family</a>
    </div>

    <x-card title="Payment details">
        <dl class="kv">
            <div class="kv-row"><dt>Family</dt><dd>{{ $payment->family->family_code }}</dd></div>
            <div class="kv-row"><dt>Payment reference</dt><dd>{{ $payment->payment_reference }}</dd></div>
            <div class="kv-row"><dt>Method</dt><dd>{{ $payment->method }}</dd></div>
            <div class="kv-row"><dt>Paid at</dt><dd>{{ $payment->paid_at->toDateString() }}</dd></div>
            <div class="kv-row"><dt>Amount</dt><dd>{{ $payment->amount }}</dd></div>
            <div class="kv-row"><dt>Notes</dt><dd>{{ $payment->notes }}</dd></div>
        </dl>
    </x-card>

    <x-card title="Allocations">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Student</th><th>Description</th><th class="num">Allocated</th></tr></thead>
                <tbody>
                @forelse ($payment->allocations as $allocation)
                    <tr>
                        <td>{{ $allocation->studentDueItem->student->name }}</td>
                        <td>{{ $allocation->studentDueItem->description }}</td>
                        <td class="num">{{ $allocation->amount }}</td>
                    </tr>
                @empty
                    <tr class="table-empty"><td colspan="3">No allocations.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    @if ($payment->receipt)
        <div class="page-actions">
            <x-button-link :href="route('receipts.show', $payment->receipt)">
                View receipt {{ $payment->receipt->receipt_no }}
            </x-button-link>
        </div>
    @endif
@endsection
