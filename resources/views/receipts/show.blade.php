@extends('layouts.app')

@section('title', 'Receipt '.$receipt->receipt_no)

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('dues-dashboard.index') }}">Dues dashboard</a>
        <span class="breadcrumb-sep">/</span>
        <a href="{{ route('payments.show', $receipt->payment_id) }}">Payment {{ $receipt->payment_id }}</a>
        <span class="breadcrumb-sep">/</span>
        <span>Receipt {{ $receipt->receipt_no }}</span>
    </div>

    <x-page-header :title="'Receipt '.$receipt->receipt_no"
                   subtitle="Snapshot captured when the payment was recorded."
                   eyebrow="Payments" />

    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('payments.show', $receipt->payment_id) }}">Back to payment</a>
        <a class="btn btn-secondary" href="{{ route('dues-dashboard.index') }}">View dashboard</a>
        @if (auth()->user()?->hasRole(\App\Models\Role::ADMIN))
            <a class="btn btn-secondary" href="{{ route('receipts.pdf', $receipt) }}">Download PDF</a>
        @endif
    </div>

    <x-card>
        <div class="receipt-sheet" data-testid="receipt-preview">
            <div class="receipt-head">
                <div>
                    <span class="receipt-brand">Skooly</span>
                    <div class="muted" style="margin-top: 0.2rem">Payment receipt</div>
                </div>
                <div class="receipt-meta">
                    <div class="receipt-no">{{ $receipt->receipt_no }}</div>
                    <div class="receipt-issued">Issued {{ $receipt->issued_at->toDateString() }}</div>
                </div>
            </div>

            <div class="receipt-parties">
                <div>
                    <span class="receipt-party-label">Family</span>
                    <span class="receipt-party-value">{{ $receipt->family_snapshot['family_code'] ?? '—' }}</span>
                </div>
                <div>
                    <span class="receipt-party-label">Method</span>
                    <span class="receipt-party-value">{{ $receipt->payment_snapshot['method'] ?? '—' }}</span>
                </div>
                <div>
                    <span class="receipt-party-label">Reference</span>
                    <span class="receipt-party-value">{{ $receipt->payment_snapshot['payment_reference'] ?? '—' }}</span>
                </div>
            </div>

            <div class="table-wrap" style="margin-top: 1.2rem">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Description</th>
                        <th class="num">Original</th>
                        <th class="num">Discount</th>
                        <th class="num">Allocated</th>
                        <th class="num">Balance after</th>
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

            <div class="receipt-total">
                <span class="receipt-total-label">Total received</span>
                <span class="receipt-total-value">{{ $receipt->total_amount }}</span>
            </div>

            <p class="receipt-foot">
                This receipt reproduces the stored snapshot from the time of payment. It is not recalculated from live due items.
                Use your browser print action for a paper copy or download the stored snapshot as a PDF.
            </p>
        </div>
    </x-card>
@endsection
