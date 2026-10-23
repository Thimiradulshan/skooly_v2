@extends('layouts.app')
@section('title', 'Payment history')
@section('content')
<x-page-header title="Payment history" subtitle="Allocation-level payments for explicitly linked students only." eyebrow="Guardian portal" />
<div class="page-actions"><x-button-link :href="route('guardian.portal')" variant="quiet">Dashboard</x-button-link><x-button-link :href="route('guardian.dues')" variant="quiet">Fees &amp; dues</x-button-link></div><p class="note">Read-only allocation history. Reversal and receipt totals are omitted because they may include other students.</p>
<x-card title="Payments"><div class="table-wrap"><table class="table"><thead><tr><th>Date</th><th>Student</th><th>Due item</th><th>Payment reference</th><th>Allocated amount</th></tr></thead><tbody>@forelse($allocations as $allocation)<tr><td>{{ $allocation->payment->paid_at?->toDateString() }}</td><td>{{ $allocation->studentDueItem->student->name }}</td><td>{{ $allocation->studentDueItem->description }}</td><td>{{ $allocation->payment->payment_reference }}</td><td>{{ $allocation->amount }}</td></tr>@empty<tr class="table-empty"><td colspan="5">No authorized payment allocations are available.</td></tr>@endforelse</tbody></table></div><x-pagination :paginator="$allocations" /></x-card><p class="note">Family receipts and payment totals are not shown because they can include other students.</p>
@endsection
