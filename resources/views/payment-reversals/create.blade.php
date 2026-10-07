@extends('layouts.app')

@section('title', 'Request payment reversal')

@section('content')
    <x-page-header title="Request payment reversal" subtitle="Select the exact amount to reopen from each original allocation. Original financial records remain unchanged." eyebrow="Payments" />
    <x-card title="Payment {{ $payment->id }}">
        <dl class="kv"><div class="kv-row"><dt>Family</dt><dd>{{ $payment->family->family_code }}</dd></div><div class="kv-row"><dt>Amount</dt><dd>{{ $payment->amount }}</dd></div></dl>
    </x-card>
    <x-card title="Select allocations">
        <p class="form-hint">Leave an amount blank to exclude that allocation. You cannot reverse more than the amount still unreversed.</p>
        <form id="payment-reversal-form" method="POST" action="{{ route('payments.reversals.store', $payment) }}" data-loading>
            @csrf
            <div class="table-wrap"><table class="table"><thead><tr><th>Due item</th><th>Original allocation</th><th>Still reversible</th><th>Reverse now</th></tr></thead><tbody>
                @foreach ($payment->allocations as $allocation)
                    <tr>
                        <td>{{ $allocation->studentDueItem->description }}</td>
                        <td>{{ $allocation->amount }}</td>
                        <td>{{ $allocation->reversible_amount }}</td>
                        <td><input class="form-control" type="number" min="0.01" max="{{ $allocation->reversible_amount }}" step="0.01" name="allocations[{{ $allocation->id }}]" value="{{ old('allocations.'.$allocation->id) }}" @disabled($allocation->reversible_amount === '0.00')></td>
                    </tr>
                @endforeach
            </tbody></table></div>
        </form>
    </x-card>
    <x-card title="Reason">
        <div class="form-field"><label class="form-label" for="reason">Reason</label><textarea class="form-control" id="reason" name="reason" form="payment-reversal-form" required>{{ old('reason') }}</textarea></div>
        <div class="btn-row"><button class="btn" type="submit" form="payment-reversal-form">Request reversal</button></div>
    </x-card>
@endsection
