@extends('layouts.app')

@section('title', 'Recurring due generation')

@section('content')
    <x-page-header title="Recurring due generation"
                   subtitle="Turn recurring fee structures into payable due items for a cycle."
                   eyebrow="Fees and dues" />

    <div class="page-actions">
        <x-button-link :href="route('dues-dashboard.index')" variant="secondary">Back to dues dashboard</x-button-link>
        <x-button-link :href="route('fee-structures.index')" variant="secondary">Fee structures</x-button-link>
    </div>

    <x-card>
        <form method="POST" action="{{ route('due-generation.recurring.store') }}"
              data-confirm="Generate recurring due items for the selected cycle? Existing due items are never changed, and running this twice is safe."
              data-confirm-title="Generate recurring dues"
              data-confirm-action="Generate dues"
              data-loading>
            @csrf

            <div class="form-grid">
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
                    <label class="form-label" for="due_date">Due date <span class="req">*</span></label>
                    <input class="form-control" type="date" id="due_date" name="due_date"
                           value="{{ old('due_date', now()->toDateString()) }}" required>
                </div>

                <div class="form-field">
                    <label class="form-label" for="cycle_key">Cycle key</label>
                    <input class="form-control" type="text" id="cycle_key" name="cycle_key"
                           value="{{ old('cycle_key') }}" placeholder="2026-10">
                    <span class="form-help">Leave blank to use the due date month. Running twice for the same cycle is safe.</span>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn">Generate recurring dues</button>
                <a class="btn btn-secondary" href="{{ route('dues-dashboard.index') }}">Cancel</a>
            </div>
        </form>
    </x-card>

    <div class="callout">
        <span class="callout-mark" aria-hidden="true">i</span>
        <p>Generation respects opt-in subscriptions and applies each student's active discounts. Opt-in categories are only charged to students with an active subscription.</p>
    </div>
@endsection
