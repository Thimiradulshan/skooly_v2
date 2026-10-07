@extends('layouts.app')

@section('title', 'Receipts')

@section('content')
    <x-page-header title="Receipts" subtitle="Stored payment receipt snapshots." eyebrow="Payments" />

    <x-card title="Search and sort">
        <form method="GET" action="{{ route('receipts.index') }}">
            <div class="form-grid">
                <div class="form-field"><label class="form-label" for="search">Family, reference, or receipt</label><input class="form-control" id="search" name="search" value="{{ $search }}"></div>
                <div class="form-field"><label class="form-label" for="sort">Sort by</label><select class="form-control" id="sort" name="sort"><option value="issued_at" @selected($sort === 'issued_at')>Issued date</option><option value="total_amount" @selected($sort === 'total_amount')>Amount</option></select></div>
                <div class="form-field"><label class="form-label" for="direction">Direction</label><select class="form-control" id="direction" name="direction"><option value="desc" @selected($direction === 'desc')>Newest / highest first</option><option value="asc" @selected($direction === 'asc')>Oldest / lowest first</option></select></div>
            </div>
            <div class="btn-row"><button type="submit" class="btn">Apply</button></div>
        </form>
    </x-card>

    <div class="table-wrap"><table class="table"><thead><tr><th>Receipt</th><th>Family</th><th>Reference</th><th>Issued at</th><th class="num">Amount</th></tr></thead><tbody>
        @forelse ($receipts as $receipt)
            <tr><td><a href="{{ route('receipts.show', $receipt) }}">{{ $receipt->receipt_no }}</a></td><td>{{ $receipt->payment->family->family_code }}</td><td>{{ $receipt->payment_snapshot['payment_reference'] ?? '' }}</td><td>{{ $receipt->issued_at->toDateString() }}</td><td class="num">{{ $receipt->total_amount }}</td></tr>
        @empty
            <tr class="table-empty"><td colspan="5"><span class="empty-state-title">No receipts found</span>Receipts are created when staff record a payment.</td></tr>
        @endforelse
    </tbody></table></div>
    <x-pagination :paginator="$receipts" />
@endsection
