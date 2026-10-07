@extends('layouts.app')

@section('title', 'Promotion batches')

@section('content')
    <x-page-header title="Promotion batches"
                   subtitle="Move students from one academic year into the next." />

    <div class="page-actions">
        <x-button-link :href="route('promotion-batches.create')">Create promotion batch</x-button-link>
    </div>

    <x-list-search :action="route('promotion-batches.index')" label="Source or target academic year" :value="$search" />

    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr><th>Source year</th><th>Target year</th><th>Status</th><th class="num">Items</th><th>Confirmed at</th></tr>
            </thead>
            <tbody>
            @forelse ($promotionBatches as $promotionBatch)
                <tr>
                    <td><a href="{{ route('promotion-batches.show', $promotionBatch) }}">{{ $promotionBatch->sourceAcademicYear->name }}</a></td>
                    <td>{{ $promotionBatch->targetAcademicYear->name }}</td>
                    <td><x-status-badge :value="$promotionBatch->status" /></td>
                    <td class="num">{{ $promotionBatch->items_count }}</td>
                    <td>{{ $promotionBatch->confirmed_at?->toDateTimeString() }}</td>
                </tr>
            @empty
                <tr class="table-empty">
                    <td colspan="5">
                        <span class="empty-state-title">No promotion batches yet</span>
                        Create a draft to review target grades before confirming.
                        <div class="empty-actions">
                            <x-button-link :href="route('promotion-batches.create')" size="small">Create promotion batch</x-button-link>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :paginator="$promotionBatches" />
@endsection
