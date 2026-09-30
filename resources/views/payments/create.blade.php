@extends('layouts.app')

@section('title', 'Record payment')

@section('content')
    <x-page-header :title="'Record a payment for '.$family->family_code"
                   subtitle="Allocate the money manually against outstanding due items." />

    @if ($dueItems->isEmpty())
        <x-empty-state title="No outstanding due items"
                       description="Generate due items before recording a payment for this family." />

        <div class="page-actions">
            <x-button-link :href="route('due-generation.recurring.create')" variant="secondary">Generate recurring dues</x-button-link>
            <a class="btn btn-secondary" href="{{ route('families.show', $family) }}">Back to family</a>
        </div>
    @else
        <x-card>
            <form method="POST" action="{{ route('families.payments.store', $family) }}">
                @csrf

                <div class="form-grid">
                    <div class="form-field">
                        <label class="form-label" for="receipt_no">Receipt number <span class="req">*</span></label>
                        <input class="form-control" type="text" id="receipt_no" name="receipt_no"
                               value="{{ old('receipt_no') }}" required>
                        <span class="form-help">Must be unique. There is no generated sequence yet.</span>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="method">Method <span class="req">*</span></label>
                        <input class="form-control" type="text" id="method" name="method"
                               value="{{ old('method', 'cash') }}" required>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="paid_at">Paid at <span class="req">*</span></label>
                        <input class="form-control" type="date" id="paid_at" name="paid_at"
                               value="{{ old('paid_at', now()->toDateString()) }}" required>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="amount">Total payment amount <span class="req">*</span></label>
                        <input class="form-control" type="number" min="0.01" step="0.01" id="amount" name="amount"
                               value="{{ old('amount') }}" required>
                        <span class="form-help">Must equal the sum of the allocations below.</span>
                    </div>

                    <div class="form-field form-field-full">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <h2 class="section-heading">Manual allocations</h2>
                <p class="note">Enter an amount against a due item only if you are settling it. Leave a row blank to skip it.</p>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Student</th><th>Admission no.</th><th>Description</th>
                            <th>Due date</th><th class="num">Balance</th><th class="num">Allocate</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($dueItems as $index => $dueItem)
                            <tr>
                                <td>{{ $dueItem->student->name }}</td>
                                <td>{{ $dueItem->student->admission_no }}</td>
                                <td>{{ $dueItem->description }}</td>
                                <td>{{ $dueItem->due_date?->toDateString() }}</td>
                                <td class="num">{{ $dueItem->balance_amount }}</td>
                                <td class="num">
                                    <input type="hidden" name="allocations[{{ $index }}][student_due_item_id]" value="{{ $dueItem->id }}">
                                    <input class="form-control compact-input" type="number" min="0.01" step="0.01"
                                           name="allocations[{{ $index }}][amount]"
                                           value="{{ old("allocations.$index.amount") }}">
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn">Record manual payment</button>
                    <a class="btn btn-secondary" href="{{ route('families.show', $family) }}">Cancel</a>
                </div>
            </form>
        </x-card>
    @endif
@endsection
