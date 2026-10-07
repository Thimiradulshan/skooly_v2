@extends('layouts.app')

@section('title', 'Payment history')

@section('content')
    <x-page-header title="Payment history" subtitle="Recorded payments and their issued receipts." eyebrow="Payments" />

    <x-card title="Search and sort">
        <form method="GET" action="{{ route('payments.index') }}">
            <div class="form-grid">
                <div class="form-field"><label class="form-label" for="search">Family, reference, or receipt</label><input class="form-control" id="search" name="search" value="{{ $search }}"></div>
                <div class="form-field"><label class="form-label" for="sort">Sort by</label><select class="form-control" id="sort" name="sort"><option value="paid_at" @selected($sort === 'paid_at')>Paid date</option><option value="amount" @selected($sort === 'amount')>Amount</option></select></div>
                <div class="form-field"><label class="form-label" for="direction">Direction</label><select class="form-control" id="direction" name="direction"><option value="desc" @selected($direction === 'desc')>Newest / highest first</option><option value="asc" @selected($direction === 'asc')>Oldest / lowest first</option></select></div>
            </div>
            <div class="btn-row"><button type="submit" class="btn">Apply</button></div>
        </form>
    </x-card>

    <div class="table-wrap"><table class="table"><thead><tr><th>Family</th><th>Reference</th><th>Method</th><th>Paid at</th><th class="num">Amount</th><th>Receipt</th></tr></thead><tbody>
        @forelse ($payments as $payment)
            <tr><td>{{ $payment->family->family_code }}</td><td><a href="{{ route('payments.show', $payment) }}">{{ $payment->payment_reference ?: 'Payment '.$payment->id }}</a></td><td>{{ $payment->method }}</td><td>{{ $payment->paid_at->toDateString() }}</td><td class="num">{{ $payment->amount }}</td><td>@if ($payment->receipt)<a href="{{ route('receipts.show', $payment->receipt) }}">{{ $payment->receipt->receipt_no }}</a>@endif</td></tr>
        @empty
            <tr class="table-empty"><td colspan="6"><span class="empty-state-title">No payments found</span>Record a payment from a family page to create history.</td></tr>
        @endforelse
    </tbody></table></div>
    <x-pagination :paginator="$payments" />
@endsection
