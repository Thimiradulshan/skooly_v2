@extends('layouts.app')

@section('title', 'Promotion batch')
@section('heading', 'Promotion batch')

@section('content')
    <p><a href="{{ route('promotion-batches.index') }}">Back to promotion batches</a></p>

    <table>
        <tbody>
        <tr><th>Source academic year</th><td>{{ $promotionBatch->sourceAcademicYear->name }}</td></tr>
        <tr><th>Target academic year</th><td>{{ $promotionBatch->targetAcademicYear->name }}</td></tr>
        <tr><th>Status</th><td>{{ $promotionBatch->status }}</td></tr>
        <tr><th>Confirmed at</th><td>{{ $promotionBatch->confirmed_at?->toDateTimeString() }}</td></tr>
        </tbody>
    </table>

    <h2>Source sections</h2>
    <ul>
        @foreach ($promotionBatch->sections as $batchSection)
            <li>{{ $batchSection->sourceSection->grade->name }} - {{ $batchSection->sourceSection->name }}</li>
        @endforeach
    </ul>

    <h2>Promotion items</h2>
    <table>
        <thead>
        <tr><th>Student</th><th>Source section</th><th>Action</th><th>Target grade</th><th>Target section</th><th>Status</th><th>Applied enrollment</th></tr>
        </thead>
        <tbody>
        @forelse ($promotionBatch->items as $item)
            <tr>
                <td>{{ $item->student->name }} ({{ $item->student->admission_no }})</td>
                <td>{{ $item->sourceSection->grade->name }} - {{ $item->sourceSection->name }}</td>
                <td>{{ $item->action }}</td>
                <td>{{ $item->targetGrade?->name }}</td>
                <td>{{ $item->targetSection?->name }}</td>
                <td>{{ $item->status }}</td>
                <td>{{ $item->applied_enrollment_id }}</td>
            </tr>
        @empty
            <tr><td colspan="7">No eligible active students were found.</td></tr>
        @endforelse
        </tbody>
    </table>

    @if ($promotionBatch->status === \App\Models\PromotionBatch::STATUS_DRAFT)
        <form method="POST" action="{{ route('promotion-batches.confirm', $promotionBatch) }}">
            @csrf
            <button type="submit">Confirm promotion batch</button>
        </form>
        <p>Confirmation creates target-year enrollments atomically. It never changes source-year enrollments or creates due items.</p>
    @endif
@endsection
