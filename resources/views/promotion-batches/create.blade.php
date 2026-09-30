@extends('layouts.app')

@section('title', 'Create promotion batch')
@section('heading', 'Create promotion batch')

@section('content')
    <p>Creating a batch only creates a draft. It does not modify students or enrollments.</p>

    <form method="POST" action="{{ route('promotion-batches.store') }}">
        @csrf

        <label for="source_academic_year_id">Source academic year</label>
        <select id="source_academic_year_id" name="source_academic_year_id" required>
            <option value="">-- choose --</option>
            @foreach ($academicYears as $academicYear)
                <option value="{{ $academicYear->id }}" {{ (int) old('source_academic_year_id') === $academicYear->id ? 'selected' : '' }}>
                    {{ $academicYear->name }}
                </option>
            @endforeach
        </select>

        <label for="target_academic_year_id">Target academic year</label>
        <select id="target_academic_year_id" name="target_academic_year_id" required>
            <option value="">-- choose --</option>
            @foreach ($academicYears as $academicYear)
                <option value="{{ $academicYear->id }}" {{ (int) old('target_academic_year_id') === $academicYear->id ? 'selected' : '' }}>
                    {{ $academicYear->name }}
                </option>
            @endforeach
        </select>

        <h2>Source sections</h2>
        <p>Select one or more sections. Default targets are determined by the existing promotion action.</p>
        @foreach ($sections as $section)
            <label for="section_{{ $section->id }}">
                <input type="checkbox" id="section_{{ $section->id }}" name="source_section_ids[]" value="{{ $section->id }}"
                    {{ in_array($section->id, (array) old('source_section_ids', [])) ? 'checked' : '' }}>
                {{ $section->grade->name }} - {{ $section->name }}
            </label>
        @endforeach

        <p><button type="submit">Create draft batch</button> <a href="{{ route('promotion-batches.index') }}">Cancel</a></p>
    </form>
@endsection
