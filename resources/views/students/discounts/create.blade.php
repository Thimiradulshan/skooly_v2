@extends('layouts.app')

@section('title', 'Apply discount')
@section('heading', 'Apply discount to '.$student->name)

@section('content')
    <form method="POST" action="{{ route('students.discounts.store', $student) }}">
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

        <label for="type">Type</label>
        <input type="text" id="type" name="type" value="{{ old('type') }}" required>

        <label for="value">Value</label>
        <input type="number" step="0.01" min="0" id="value" name="value" value="{{ old('value') }}" required>

        <label for="value_type">Value type</label>
        <select id="value_type" name="value_type">
            <option value="">-- treat as amount --</option>
            <option value="amount" {{ old('value_type') === 'amount' ? 'selected' : '' }}>Amount</option>
            <option value="percentage" {{ old('value_type') === 'percentage' ? 'selected' : '' }}>Percentage</option>
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
            <button type="submit">Apply discount</button>
            <a href="{{ route('families.show', $student->family_id) }}">Cancel</a>
        </p>
    </form>

    <p>Discounts are applied when due items are generated. Existing due items are not changed.</p>
@endsection
