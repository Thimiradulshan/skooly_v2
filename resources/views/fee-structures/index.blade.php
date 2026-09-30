@extends('layouts.app')

@section('title', 'Fee structures')
@section('heading', 'Fee structures')

@section('content')
    <p>
        <a href="{{ route('fee-structures.create') }}">Create fee structure</a>
        &middot;
        <a href="{{ route('fee-categories.index') }}">Fee categories</a>
    </p>

    <table>
        <thead>
        <tr>
            <th>Category</th>
            <th>Grade</th>
            <th>Academic year</th>
            <th>Amount</th>
            <th>Frequency</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($feeStructures as $feeStructure)
            <tr>
                <td>{{ $feeStructure->feeCategory?->name }}</td>
                <td>{{ $feeStructure->grade?->name }}</td>
                <td>{{ $feeStructure->academicYear?->name }}</td>
                <td>{{ $feeStructure->amount }}</td>
                <td>{{ $feeStructure->frequency }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No fee structures yet.</td></tr>
        @endforelse
        </tbody>
    </table>

    <p>Fee structures are configuration. Changing them never rewrites existing due items.</p>
@endsection
