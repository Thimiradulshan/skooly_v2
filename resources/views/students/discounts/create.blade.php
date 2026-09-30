@extends('layouts.app')

@section('title', 'Apply discount')

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('families.index') }}">Families</a>
        <span class="breadcrumb-sep">/</span>
        <a href="{{ route('families.show', $student->family_id) }}">{{ $student->family?->family_code }}</a>
        <span class="breadcrumb-sep">/</span>
        <span>{{ $student->name }}</span>
    </div>

    <x-page-header :title="'Apply a discount to '.$student->name"
                   subtitle="The discount applies when due items are generated."
                   eyebrow="Fees and dues" />

    <x-card>
        <form method="POST" action="{{ route('students.discounts.store', $student) }}" data-loading>
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
                    <label class="form-label" for="type">Type <span class="req">*</span></label>
                    <input class="form-control" type="text" id="type" name="type" value="{{ old('type') }}" required>
                </div>

                <div class="form-field">
                    <label class="form-label" for="value">Value <span class="req">*</span></label>
                    <input class="form-control" type="number" min="0" step="0.01" id="value" name="value"
                           value="{{ old('value') }}" required>
                </div>

                <div class="form-field">
                    <label class="form-label" for="value_type">Value type</label>
                    <select class="form-control" id="value_type" name="value_type">
                        <option value="">-- treat as amount --</option>
                        <option value="amount" {{ old('value_type') === 'amount' ? 'selected' : '' }}>Amount</option>
                        <option value="percentage" {{ old('value_type') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="starts_on">Starts on</label>
                    <input class="form-control" type="date" id="starts_on" name="starts_on" value="{{ old('starts_on') }}">
                </div>

                <div class="form-field">
                    <label class="form-label" for="ends_on">Ends on</label>
                    <input class="form-control" type="date" id="ends_on" name="ends_on" value="{{ old('ends_on') }}">
                </div>
            </div>

            <div class="checkbox-field">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                       {{ old('is_active', '1') ? 'checked' : '' }}>
                <label for="is_active">Active</label>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn">Apply discount</button>
                <a class="btn btn-secondary" href="{{ route('families.show', $student->family_id) }}">Cancel</a>
            </div>
        </form>
    </x-card>

    <p class="note">Existing due items are never changed. The discount is applied and snapshotted the next time dues are generated.</p>
@endsection
