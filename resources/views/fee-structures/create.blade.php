@extends('layouts.app')

@section('title', 'Create fee structure')
@section('heading', 'Create fee structure')

@section('content')
    <form method="POST" action="{{ route('fee-structures.store') }}">
        @csrf

        <label for="fee_category_id">Fee category</label>
        <select id="fee_category_id" name="fee_category_id" required>
            <option value="">-- choose --</option>
            @foreach ($feeCategories as $feeCategory)
                <option value="{{ $feeCategory->id }}"
                    {{ (int) old('fee_category_id') === $feeCategory->id ? 'selected' : '' }}>
                    {{ $feeCategory->name }}
                </option>
            @endforeach
        </select>

        <label for="grade_id">Grade</label>
        <select id="grade_id" name="grade_id" required>
            <option value="">-- choose --</option>
            @foreach ($grades as $grade)
                <option value="{{ $grade->id }}" {{ (int) old('grade_id') === $grade->id ? 'selected' : '' }}>
                    {{ $grade->name }}
                </option>
            @endforeach
        </select>

        <label for="academic_year_id">Academic year</label>
        <select id="academic_year_id" name="academic_year_id" required>
            <option value="">-- choose --</option>
            @foreach ($academicYears as $academicYear)
                <option value="{{ $academicYear->id }}"
                    {{ (int) old('academic_year_id') === $academicYear->id ? 'selected' : '' }}>
                    {{ $academicYear->name }}
                </option>
            @endforeach
        </select>

        <label for="amount">Amount</label>
        <input type="number" step="0.01" min="0" id="amount" name="amount" value="{{ old('amount') }}" required>

        <label for="frequency">Frequency</label>
        <input type="text" id="frequency" name="frequency" value="{{ old('frequency') }}" required>

        <p>
            <button type="submit">Create fee structure</button>
            <a href="{{ route('fee-structures.index') }}">Cancel</a>
        </p>
    </form>
@endsection
