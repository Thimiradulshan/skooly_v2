@extends('layouts.app')

@section('title', 'Create promotion batch')

@section('content')
    <x-page-header title="Create promotion batch"
                   subtitle="A draft only. Nothing changes until you confirm it." />

    <x-card>
        <form method="POST" action="{{ route('promotion-batches.store') }}" data-loading>
            @csrf

            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="source_academic_year_id">Source academic year <span class="req">*</span></label>
                    <select class="form-control" id="source_academic_year_id" name="source_academic_year_id" required>
                        <option value="">-- choose --</option>
                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear->id }}" {{ (int) old('source_academic_year_id') === $academicYear->id ? 'selected' : '' }}>
                                {{ $academicYear->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="target_academic_year_id">Target academic year <span class="req">*</span></label>
                    <select class="form-control" id="target_academic_year_id" name="target_academic_year_id" required>
                        <option value="">-- choose --</option>
                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear->id }}" {{ (int) old('target_academic_year_id') === $academicYear->id ? 'selected' : '' }}>
                                {{ $academicYear->name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="form-help">Must differ from the source year.</span>
                </div>
            </div>

            <h2 class="section-heading">Source sections</h2>
            <p class="note">Only active students enrolled in these sections will be listed.</p>
            <div class="choice-list">
                @forelse ($sections as $section)
                    <div class="checkbox-field">
                        <input type="checkbox" id="section_{{ $section->id }}" name="source_section_ids[]" value="{{ $section->id }}"
                               {{ in_array($section->id, (array) old('source_section_ids', [])) ? 'checked' : '' }}>
                        <label for="section_{{ $section->id }}">{{ $section->grade->name }} - {{ $section->name }}</label>
                    </div>
                @empty
                    <p class="muted">No sections exist yet.</p>
                @endforelse
            </div>

            <div class="btn-row">
                <button type="submit" class="btn">Create draft batch</button>
                <a class="btn btn-secondary" href="{{ route('promotion-batches.index') }}">Cancel</a>
            </div>
        </form>
    </x-card>

    <p class="note">Target grades and sections are proposed automatically. Promotion never creates fee items for the new year.</p>
@endsection
