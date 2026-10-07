@extends('layouts.app')

@section('title', 'Fee structures')

@section('content')
    <x-page-header title="Fee structures"
                   subtitle="Amounts per category, grade, academic year, and frequency." />

    <div class="page-actions">
        <x-button-link :href="route('fee-structures.create')">Create fee structure</x-button-link>
        <x-button-link :href="route('fee-categories.index')" variant="secondary">Fee categories</x-button-link>
    </div>

    <x-list-search :action="route('fee-structures.index')" label="Category, grade, or academic year" :value="$search" :sort-options="['amount' => 'Amount', 'frequency' => 'Frequency']" :sort="$sort" :direction="$direction" />

    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr><th>Category</th><th>Grade</th><th>Academic year</th><th class="num">Amount</th><th>Frequency</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($feeStructures as $feeStructure)
                <tr>
                    <td>{{ $feeStructure->feeCategory?->name }}</td>
                    <td>{{ $feeStructure->grade?->name }}</td>
                    <td>{{ $feeStructure->academicYear?->name }}</td>
                    <td class="num">{{ $feeStructure->amount }}</td>
                    <td>{{ $feeStructure->frequency }}</td>
                    <td>{{ $feeStructure->student_due_items_count > 0 ? 'Price locked' : 'Editable' }}</td>
                    <td>
                        @if ($feeStructure->student_due_items_count === 0)
                            <a class="table-action" href="{{ route('fee-structures.edit', $feeStructure) }}">Edit</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr class="table-empty">
                    <td colspan="7">
                        <span class="empty-state-title">No fee structures yet</span>
                        Set an amount per grade and academic year to enable due generation.
                        <div class="empty-actions">
                            <x-button-link :href="route('fee-structures.create')" size="small">Create fee structure</x-button-link>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <x-pagination :paginator="$feeStructures" />
    <p class="note">Amount and frequency can be edited until this structure produces a due item. Category, grade, and academic year are fixed. Generated due item snapshots are never changed.</p>
@endsection
