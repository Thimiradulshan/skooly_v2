@extends('layouts.app')

@section('title', 'Create fee structure')

@section('content')
    <x-page-header title="Create fee structure"
                   subtitle="Structures are versioned by academic year, so last year's price is never affected." />

    <x-card>
        <form method="POST" action="{{ route('fee-structures.store') }}" data-loading>
            @csrf

            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="fee_category_id">Fee category <span class="req">*</span></label>
                    <select class="form-control" id="fee_category_id" name="fee_category_id" required>
                        <option value="">-- choose --</option>
                        @foreach ($feeCategories as $feeCategory)
                            <option value="{{ $feeCategory->id }}" {{ (int) old('fee_category_id') === $feeCategory->id ? 'selected' : '' }}>
                                {{ $feeCategory->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="grade_id">Grade <span class="req">*</span></label>
                    <select class="form-control" id="grade_id" name="grade_id" required>
                        <option value="">-- choose --</option>
                        @foreach ($grades as $grade)
                            <option value="{{ $grade->id }}" {{ (int) old('grade_id') === $grade->id ? 'selected' : '' }}>
                                {{ $grade->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="academic_year_id">Academic year <span class="req">*</span></label>
                    <select class="form-control" id="academic_year_id" name="academic_year_id" required>
                        <option value="">-- choose --</option>
                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear->id }}" {{ (int) old('academic_year_id') === $academicYear->id ? 'selected' : '' }}>
                                {{ $academicYear->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="frequency">Frequency <span class="req">*</span></label>
                    <input class="form-control" type="text" id="frequency" name="frequency"
                           value="{{ old('frequency') }}" required placeholder="monthly, termly, yearly">
                </div>

                <div class="form-field">
                    <label class="form-label" for="amount">Amount <span class="req">*</span></label>
                    <input class="form-control" type="number" min="0" step="0.01" id="amount" name="amount"
                           value="{{ old('amount') }}" required>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn">Create fee structure</button>
                <a class="btn btn-secondary" href="{{ route('fee-structures.index') }}">Cancel</a>
            </div>
        </form>
    </x-card>
@endsection
