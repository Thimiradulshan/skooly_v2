@extends('layouts.app')

@section('title', 'Add fee subscription')
@section('heading', 'Add fee subscription for '.$student->name)

@section('content')
    <form method="POST" action="{{ route('students.fee-subscriptions.store', $student) }}">
        @csrf

        <label for="fee_category_id">Opt-in fee category</label>
        <select id="fee_category_id" name="fee_category_id" required>
            <option value="">-- choose --</option>
            @foreach ($feeCategories as $feeCategory)
                <option value="{{ $feeCategory->id }}"
                    {{ (int) old('fee_category_id') === $feeCategory->id ? 'selected' : '' }}>
                    {{ $feeCategory->name }}
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

        <label for="is_active">
            <input type="checkbox" id="is_active" name="is_active" value="1"
                   {{ old('is_active', '1') ? 'checked' : '' }}>
            Active
        </label>

        <label for="starts_on">Starts on</label>
        <input type="date" id="starts_on" name="starts_on" value="{{ old('starts_on') }}">

        <label for="ends_on">Ends on</label>
        <input type="date" id="ends_on" name="ends_on" value="{{ old('ends_on') }}">

        <p>
            <button type="submit">Add subscription</button>
            <a href="{{ route('families.show', $student->family_id) }}">Cancel</a>
        </p>
    </form>

    <p>Only opt-in fee categories can be subscribed.</p>
@endsection
