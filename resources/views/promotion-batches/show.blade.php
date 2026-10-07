@extends('layouts.app')

@section('title', 'Promotion batch')

@section('content')
    <x-page-header title="Promotion batch" subtitle="Review the items, then confirm to apply them." eyebrow="Academic progress" />

    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('promotion-batches.index') }}">Back to promotion batches</a>
    </div>

    <x-card title="Batch">
        <dl class="kv">
            <div class="kv-row"><dt>Source academic year</dt><dd>{{ $promotionBatch->sourceAcademicYear->name }}</dd></div>
            <div class="kv-row"><dt>Target academic year</dt><dd>{{ $promotionBatch->targetAcademicYear->name }}</dd></div>
            <div class="kv-row"><dt>Status</dt><dd><x-status-badge :value="$promotionBatch->status" /></dd></div>
            <div class="kv-row"><dt>Confirmed at</dt><dd>{{ $promotionBatch->confirmed_at?->toDateTimeString() }}</dd></div>
        </dl>
    </x-card>

    <x-card title="Source sections">
        <div class="choice-list">
            @foreach ($promotionBatch->sections as $batchSection)
                <p>{{ $batchSection->sourceSection->grade->name }} - {{ $batchSection->sourceSection->name }}</p>
            @endforeach
        </div>
    </x-card>

    <x-card title="Promotion items">
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Student</th><th>Source section</th><th>Action</th>
                    <th>Target grade</th><th>Target section</th><th>Status</th><th>Applied enrollment</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($promotionBatch->items as $item)
                    <tr>
                        <td>{{ $item->student->name }} ({{ $item->student->admission_no }})</td>
                        <td>{{ $item->sourceSection->grade->name }} - {{ $item->sourceSection->name }}</td>
                        <td><x-status-badge :value="$item->action" /></td>
                        <td>{{ $item->targetGrade?->name }}</td>
                        <td>{{ $item->targetSection?->name }}</td>
                        <td><x-status-badge :value="$item->status" /></td>
                        <td>{{ $item->applied_enrollment_id }}</td>
                    </tr>
                @empty
                    <tr class="table-empty"><td colspan="7">No eligible active students were found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    @if ($promotionBatch->status === \App\Models\PromotionBatch::STATUS_DRAFT)
        <div class="callout">
            <span class="callout-mark" aria-hidden="true">!</span>
            <p>Confirming creates target-year enrollments in one transaction and never changes source-year enrollments. It does not create any fee items, and it cannot be reversed from this screen.</p>
        </div>

        <form method="POST" action="{{ route('promotion-batches.confirm', $promotionBatch) }}"
              data-confirm="Confirm this promotion batch? Target-year enrollments will be created for every eligible item. This cannot be undone here."
              data-confirm-title="Confirm promotion batch"
              data-confirm-action="Confirm promotion"
              data-confirm-tone="danger"
              data-loading>
            @csrf
            <button type="submit" class="btn btn-danger">Confirm promotion batch</button>
        </form>

        <form method="POST" action="{{ route('promotion-batches.discard', $promotionBatch) }}"
              data-confirm="Discard this draft? No student enrollments will be created or changed."
              data-confirm-title="Discard promotion batch"
              data-confirm-action="Discard draft"
              data-loading>
            @csrf
            <button type="submit" class="btn btn-secondary">Discard draft</button>
        </form>
    @endif
@endsection
