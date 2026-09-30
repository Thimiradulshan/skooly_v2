@extends('layouts.app')

@section('title', 'Receipt '.$receipt->receipt_no)

@section('content')
    <x-page-header :title="'Receipt '.$receipt->receipt_no"
                   subtitle="Snapshot captured when the payment was recorded." />

    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('payments.show', $receipt->payment_id) }}">Back to payment</a>
    </div>

    <x-card title="Receipt">
        <dl class="kv">
            <div class="kv-row"><dt>Receipt number</dt><dd>{{ $receipt->receipt_no }}</dd></div>
            <div class="kv-row"><dt>Issued at</dt><dd>{{ $receipt->issued_at->toDateString() }}</dd></div>
            <div class="kv-row"><dt>Total amount</dt><dd>{{ $receipt->total_amount }}</dd></div>
            <div class="kv-row"><dt>Family</dt><dd>{{ $receipt->family_snapshot['family_code'] ?? '' }}</dd></div>
            <div class="kv-row"><dt>Method</dt><dd>{{ $receipt->payment_snapshot['method'] ?? '' }}</dd></div>
            <div class="kv-row"><dt>Payment amount</dt><dd>{{ $receipt->payment_snapshot['amount'] ?? '' }}</dd></div>
        </dl>
    </x-card>

    <x-card title="Allocation snapshot">
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Description</th><th class="num">Original</th><th class="num">Discount</th>
                    <th class="num">Allocated</th><th class="num">Balance after</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($receipt->allocation_snapshot as $allocation)
                    <tr>
                        <td>{{ $allocation['description'] }}</td>
                        <td class="num">{{ $allocation['original_amount'] }}</td>
                        <td class="num">{{ $allocation['discount_amount'] }}</td>
                        <td class="num">{{ $allocation['allocation_amount'] }}</td>
                        <td class="num">{{ $allocation['balance_amount'] }}</td>
                    </tr>
                @empty
                    <tr class="table-empty"><td colspan="5">No stored due item snapshot.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <p class="note">This page shows stored receipt data only. It is never recalculated from live due items, and there is no PDF export yet.</p>
@endsection
