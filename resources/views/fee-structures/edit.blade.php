@extends('layouts.app')

@section('title', 'Edit fee structure')

@section('content')
    <x-page-header title="Edit fee structure" subtitle="Only amount and frequency may change before due generation." />

    <x-card>
        <dl class="kv">
            <div class="kv-row"><dt>Fee category</dt><dd>{{ $feeStructure->feeCategory->name }}</dd></div>
            <div class="kv-row"><dt>Grade</dt><dd>{{ $feeStructure->grade->name }}</dd></div>
            <div class="kv-row"><dt>Academic year</dt><dd>{{ $feeStructure->academicYear->name }}</dd></div>
        </dl>
    </x-card>

    @if ($isLocked)
        <div class="callout">
            <span class="callout-mark" aria-hidden="true">i</span>
            <p>This fee structure is locked because it has generated due items. Those stored snapshots cannot be changed.</p>
        </div>
    @else
        <x-card>
            <form method="POST" action="{{ route('fee-structures.update', $feeStructure) }}" data-loading>
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-field">
                        <label class="form-label" for="frequency">Frequency <span class="req">*</span></label>
                        <input class="form-control" type="text" id="frequency" name="frequency" value="{{ old('frequency', $feeStructure->frequency) }}" required>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="amount">Amount <span class="req">*</span></label>
                        <input class="form-control" type="number" min="0" step="0.01" id="amount" name="amount" value="{{ old('amount', $feeStructure->amount) }}" required>
                    </div>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn">Update fee structure</button>
                    <a class="btn btn-secondary" href="{{ route('fee-structures.index') }}">Cancel</a>
                </div>
            </form>
        </x-card>
    @endif
@endsection
