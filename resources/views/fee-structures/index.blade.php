@extends('layouts.app')

@section('title', 'Fee structures')

@section('content')
    <x-page-header title="Fee structures"
                   subtitle="Amounts per category, grade, academic year, and frequency." />

    <div class="page-actions">
        <x-button-link :href="route('fee-structures.create')">Create fee structure</x-button-link>
        <x-button-link :href="route('fee-categories.index')" variant="secondary">Fee categories</x-button-link>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr><th>Category</th><th>Grade</th><th>Academic year</th><th class="num">Amount</th><th>Frequency</th></tr>
            </thead>
            <tbody>
            @forelse ($feeStructures as $feeStructure)
                <tr>
                    <td>{{ $feeStructure->feeCategory?->name }}</td>
                    <td>{{ $feeStructure->grade?->name }}</td>
                    <td>{{ $feeStructure->academicYear?->name }}</td>
                    <td class="num">{{ $feeStructure->amount }}</td>
                    <td>{{ $feeStructure->frequency }}</td>
                </tr>
            @empty
                <tr class="table-empty"><td colspan="5">No fee structures yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <p class="note">Fee structures are configuration and are never edited or deleted once created. Changing them never rewrites an existing due item.</p>
@endsection
