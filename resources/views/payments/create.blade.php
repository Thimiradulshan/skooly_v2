@extends('layouts.app')

@section('title', 'Record payment')
@section('heading', 'Record payment for '.$family->family_code)

@section('content')
    <p>Allocate this payment manually. Only outstanding due items for this family are listed.</p>

    @if ($dueItems->isEmpty())
        <p>No outstanding due items exist for this family.</p>
    @else
        <form method="POST" action="{{ route('families.payments.store', $family) }}">
            @csrf

            <label for="receipt_no">Receipt number</label>
            <input type="text" id="receipt_no" name="receipt_no" value="{{ old('receipt_no') }}" required>

            <label for="method">Method</label>
            <input type="text" id="method" name="method" value="{{ old('method', 'cash') }}" required>

            <label for="paid_at">Paid at</label>
            <input type="date" id="paid_at" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" required>

            <label for="amount">Total payment amount</label>
            <input type="number" min="0.01" step="0.01" id="amount" name="amount" value="{{ old('amount') }}" required>

            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>

            <h2>Manual allocations</h2>
            <table>
                <thead>
                <tr>
                    <th>Student</th><th>Admission no.</th><th>Description</th><th>Due date</th><th>Balance</th><th>Allocate</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($dueItems as $index => $dueItem)
                    <tr>
                        <td>{{ $dueItem->student->name }}</td>
                        <td>{{ $dueItem->student->admission_no }}</td>
                        <td>{{ $dueItem->description }}</td>
                        <td>{{ $dueItem->due_date?->toDateString() }}</td>
                        <td>{{ $dueItem->balance_amount }}</td>
                        <td>
                            <input type="hidden" name="allocations[{{ $index }}][student_due_item_id]" value="{{ $dueItem->id }}">
                            <input type="number" min="0.01" step="0.01" name="allocations[{{ $index }}][amount]"
                                   value="{{ old("allocations.$index.amount") }}">
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <p><button type="submit">Record manual payment</button> <a href="{{ route('families.show', $family) }}">Cancel</a></p>
        </form>
    @endif
@endsection
