@extends('layouts.app')

@section('title', $student->name.' payment history')

@section('content')
    <div class="breadcrumb"><a href="{{ route('students.show', $student) }}">{{ $student->name }}</a><span class="breadcrumb-sep">/</span><span>Payment history</span></div>
    <x-page-header :title="$student->name.' payment history'" subtitle="Read-only allocations recorded against this student's due items." eyebrow="Payments" />
    <div class="page-actions"><x-button-link :href="route('students.show', $student)" variant="quiet">Back to student</x-button-link></div>
    <x-card title="Payment allocations"><div class="table-wrap"><table class="table"><thead><tr><th>Paid at</th><th>Due item</th><th class="num">Allocated</th><th>Reference</th><th>Receipt</th><th>Correction status</th></tr></thead><tbody>@forelse ($allocations as $allocation)<tr><td>{{ $allocation->payment->paid_at->toDateString() }}</td><td>{{ $allocation->studentDueItem->description }}</td><td class="num">{{ $allocation->amount }}</td><td>{{ $allocation->payment->payment_reference ?: '—' }}</td><td>@if ($allocation->payment->receipt)<a href="{{ route('receipts.show', $allocation->payment->receipt) }}">{{ $allocation->payment->receipt->receipt_no }}</a>@else — @endif</td><td>@forelse ($allocation->reversalAllocations as $reversalAllocation)<a href="{{ route('payment-reversals.show', $reversalAllocation->paymentReversal) }}">{{ str($reversalAllocation->paymentReversal->status)->headline() }}@if ($reversalAllocation->paymentReversal->correctionReceipt) ({{ $reversalAllocation->paymentReversal->correctionReceipt->receipt_no }})@endif</a>@if (! $loop->last)<br>@endif @empty — @endforelse</td></tr>@empty<tr class="table-empty"><td colspan="6"><span class="empty-state-title">No payment allocations recorded</span>Payments allocated to this student's due items will appear here.</td></tr>@endforelse</tbody></table></div></x-card>
    <x-pagination :paginator="$allocations" />
@endsection
