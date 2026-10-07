@extends('layouts.app')

@section('title', 'Generate payment reminders')

@section('content')
    <x-page-header title="Generate reminder records"
                   subtitle="Finds guardians linked to students with outstanding due items."
                   eyebrow="Communication" />

    <div class="page-actions">
        <x-button-link :href="route('payment-reminders.index')" variant="secondary">Back to reminders</x-button-link>
    </div>

    <x-card>
        <form method="POST" action="{{ route('payment-reminders.store') }}"
              data-confirm="Generate reminder records for the selected date? Only guardians explicitly linked to a student will be included."
              data-confirm-title="Generate reminders"
              data-confirm-action="Generate reminders"
              data-loading>
            @csrf

            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="as_of_date">As of date <span class="req">*</span></label>
                    <input class="form-control" type="date" id="as_of_date" name="as_of_date"
                           value="{{ old('as_of_date', now()->toDateString()) }}" required>
                </div>

                <div class="form-field">
                    <label class="form-label" for="upcoming_window_days">Upcoming window days</label>
                    <input class="form-control" type="number" min="0" step="1" id="upcoming_window_days"
                           name="upcoming_window_days" value="{{ old('upcoming_window_days', 7) }}">
                    <span class="form-help">Due items inside this window after the as-of date count as upcoming. Anything earlier is overdue.</span>
                </div>

                <div class="form-field">
                    <label class="form-label" for="academic_year_id">Academic year (optional)</label>
                    <select class="form-control" id="academic_year_id" name="academic_year_id">
                        <option value="">-- all --</option>
                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear->id }}" {{ (int) old('academic_year_id', $activeAcademicYear->id) === $academicYear->id ? 'selected' : '' }}>
                                {{ $academicYear->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="family_id">Family (optional)</label>
                    <select class="form-control" id="family_id" name="family_id">
                        <option value="">-- all --</option>
                        @foreach ($families as $family)
                            <option value="{{ $family->id }}" {{ (int) old('family_id') === $family->id ? 'selected' : '' }}>
                                {{ $family->family_code }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn">Generate reminder records</button>
                <x-button-link :href="route('payment-reminders.index')" variant="secondary">Cancel</x-button-link>
            </div>
        </form>
    </x-card>

    <div class="callout">
        <span class="callout-mark" aria-hidden="true">i</span>
        <p>These are internal outbox records. Nothing is emailed, texted, or messaged, and reminders are never marked as sent.</p>
    </div>

    <p class="note">Only guardians explicitly linked to a student are included. Running this twice for the same as-of date is safe.</p>
@endsection
