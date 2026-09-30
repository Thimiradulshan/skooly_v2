@extends('layouts.app')

@section('title', 'Fee categories')
@section('heading', 'Fee categories')

@section('content')
    <p>
        <a href="{{ route('fee-categories.create') }}">Create fee category</a>
        &middot;
        <a href="{{ route('fee-structures.index') }}">Fee structures</a>
    </p>

    <table>
        <thead>
        <tr><th>Name</th><th>Recurring</th><th>Opt-in</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($feeCategories as $feeCategory)
            <tr>
                <td>{{ $feeCategory->name }}</td>
                <td>{{ $feeCategory->is_recurring ? 'Yes' : 'No' }}</td>
                <td>{{ $feeCategory->is_opt_in ? 'Yes' : 'No' }}</td>
                <td><a href="{{ route('fee-categories.edit', $feeCategory) }}">Edit</a></td>
            </tr>
        @empty
            <tr><td colspan="4">No fee categories yet.</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
