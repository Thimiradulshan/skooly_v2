@extends('layouts.app')

@section('title', 'Add fee subscription')

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('families.index') }}">Families</a>
        <span class="breadcrumb-sep">/</span>
        <a href="{{ route('families.show', $student->family_id) }}">{{ $student->family?->family_code }}</a>
        <span class="breadcrumb-sep">/</span>
        <span>{{ $student->name }}</span>
    </div>

    <x-page-header :title="'Add a fee subscription for '.$student->name"
                   subtitle="Only opt-in categories can be subscribed."
                   eyebrow="Fees and dues" />

    <x-card>
        <form method="POST" action="{{ route('students.fee-subscriptions.store', $student) }}" data-loading>
            @csrf

            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="fee_category_id">Opt-in fee category <span class="req">*</span></label>
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
                <button type="submit" class="btn">Add subscription</button>
                <a class="btn btn-secondary" href="{{ route('families.show', $student->family_id) }}">Cancel</a>
            </div>
        </form>
    </x-card>

    <p class="note">Only opt-in fee categories can be subscribed. A non-opt-in category is rejected.</p>
@endsection
