@extends('layouts.app')

@section('title', 'Promotion batches')
@section('heading', 'Promotion batches')

@section('content')
    <p><a href="{{ route('promotion-batches.create') }}">Create promotion batch</a></p>

    <table>
        <thead>
        <tr><th>Source year</th><th>Target year</th><th>Status</th><th>Items</th><th>Confirmed at</th></tr>
        </thead>
        <tbody>
        @forelse ($promotionBatches as $promotionBatch)
            <tr>
                <td><a href="{{ route('promotion-batches.show', $promotionBatch) }}">{{ $promotionBatch->sourceAcademicYear->name }}</a></td>
                <td>{{ $promotionBatch->targetAcademicYear->name }}</td>
                <td>{{ $promotionBatch->status }}</td>
                <td>{{ $promotionBatch->items_count }}</td>
                <td>{{ $promotionBatch->confirmed_at?->toDateTimeString() }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No promotion batches yet.</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
