@extends('layouts.app')

@section('title', 'Students')

@section('content')
    <x-page-header title="Student directory"
                    subtitle="Search registered students and review their year-specific academic placement."
                    eyebrow="Registration" />
    <div class="page-actions"><x-button-link :href="route('families.index')">Register student</x-button-link></div>

    <x-card title="Search and filters">
        <form method="GET" action="{{ route('students.index') }}">
            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="search">Student, admission number, family, or guardian</label>
                    <input class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="form-field">
                    <label class="form-label" for="academic_year_id">Academic year</label>
                    <select class="form-control" id="academic_year_id" name="academic_year_id">
                        <option value="">Current active academic year</option>
                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear->id }}" @selected(($filters['academic_year_id'] ?? null) == $academicYear->id)>{{ $academicYear->name }}</option>
                        @endforeach
                    </select>
                    <span class="form-help">Required when filtering by grade or section.</span>
                </div>
                <div class="form-field">
                    <label class="form-label" for="grade_id">Grade</label>
                    <select class="form-control" id="grade_id" name="grade_id">
                        <option value="">All grades</option>
                        @foreach ($grades as $grade)
                            <option value="{{ $grade->id }}" @selected(($filters['grade_id'] ?? null) == $grade->id)>{{ $grade->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label class="form-label" for="section_id">Section</label>
                    <select class="form-control" id="section_id" name="section_id">
                        <option value="">All sections</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected(($filters['section_id'] ?? null) == $section->id)>{{ $section->grade->name }} — {{ $section->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label class="form-label" for="status">Student status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All statuses</option>
                        @foreach ([\App\Models\Student::STATUS_PENDING_REGISTRATION, \App\Models\Student::STATUS_ACTIVE, \App\Models\Student::STATUS_INACTIVE, \App\Models\Student::STATUS_WITHDRAWN, \App\Models\Student::STATUS_GRADUATED] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ str($status)->headline() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="btn-row">
                <button type="submit" class="btn">Apply filters</button>
                <x-button-link :href="route('students.index')" variant="quiet">Reset filters</x-button-link>
            </div>
        </form>
    </x-card>

    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>Student</th>
                <th>Family</th>
                <th>Academic placement</th>
                <th>Status</th>
                <th>Linked guardians</th>
                <th class="actions">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($students as $student)
                @php($enrollment = $student->enrollments->first())
                <tr>
                    <td>
                        <a href="{{ route('students.show', $student) }}">{{ $student->name }}</a>
                        <div class="note">{{ $student->admission_no }}</div>
                    </td>
                    <td><a href="{{ route('families.show', $student->family) }}">{{ $student->family->family_code }}</a></td>
                    <td>
                        @if ($enrollment)
                            <strong>{{ $enrollment->grade->name }} {{ $enrollment->section->name }}</strong>
                            <div class="note">{{ $enrollment->academicYear->name }}</div>
                        @else
                            <span class="note">No enrollment for {{ $placementAcademicYear?->name ?? 'the active academic year' }}</span>
                        @endif
                    </td>
                    <td><x-status-badge :value="$student->status" /></td>
                    <td>
                        @forelse ($student->guardians as $guardian)
                            <a href="{{ route('guardians.show', $guardian) }}">{{ $guardian->name }}</a>{{ $loop->last ? '' : ', ' }}
                        @empty
                            <span class="note">No linked guardians</span>
                        @endforelse
                    </td>
                    <td class="actions">
                        <x-button-link :href="route('students.show', $student)" variant="quiet" size="small">View</x-button-link>
                        <x-button-link :href="route('students.edit', $student)" variant="quiet" size="small">Edit</x-button-link>
                    </td>
                </tr>
            @empty
                <tr class="table-empty">
                    <td colspan="6">
                        <span class="empty-state-title">No students match these filters</span>
                        Try a different search or reset the directory filters.
                        <div class="empty-actions"><x-button-link :href="route('students.index')" size="small">Reset filters</x-button-link></div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <x-pagination :paginator="$students" />
@endsection
