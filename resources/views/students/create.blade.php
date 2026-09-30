@extends('layouts.app')

@section('title', 'Register student')

@section('content')
    <x-page-header :title="'Register a student for '.$family->family_code"
                   subtitle="Only the guardians you tick will be able to access this student." />

    <x-card>
        <form method="POST" action="{{ route('families.students.store', $family) }}">
            @csrf

            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="name">Student name <span class="req">*</span></label>
                    <input class="form-control" type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
                </div>

                <div class="form-field">
                    <label class="form-label" for="admission_no">Admission number <span class="req">*</span></label>
                    <input class="form-control" type="text" id="admission_no" name="admission_no"
                           value="{{ old('admission_no') }}" required>
                    <span class="form-help">Must be unique across all students.</span>
                </div>

                <div class="form-field">
                    <label class="form-label" for="dob">Date of birth <span class="req">*</span></label>
                    <input class="form-control" type="date" id="dob" name="dob" value="{{ old('dob') }}" required>
                </div>

                <div class="form-field">
                    <label class="form-label" for="gender">Gender <span class="req">*</span></label>
                    <input class="form-control" type="text" id="gender" name="gender" value="{{ old('gender') }}" required>
                </div>

                <div class="form-field form-field-full">
                    <label class="form-label" for="photo_path">Photo path</label>
                    <input class="form-control" type="text" id="photo_path" name="photo_path"
                           value="{{ old('photo_path') }}">
                </div>
            </div>

            <h2 class="section-heading">Guardians</h2>
            <p class="note">A guardian only sees students they are linked to, even when the family has combined billing.</p>
            <div class="choice-list">
                @forelse ($family->guardians as $guardian)
                    <div class="checkbox-field">
                        <input type="checkbox" id="guardian_{{ $guardian->id }}" name="guardian_ids[]"
                               value="{{ $guardian->id }}"
                               {{ in_array($guardian->id, (array) old('guardian_ids', [])) ? 'checked' : '' }}>
                        <label for="guardian_{{ $guardian->id }}">{{ $guardian->name }}</label>
                    </div>
                @empty
                    <p class="muted">This family has no guardians yet.</p>
                @endforelse
            </div>

            <h2 class="section-heading">Initial enrollment (optional)</h2>
            <p class="note">Provide all three fields to create an enrollment, or leave all three blank to skip it.</p>

            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="academic_year_id">Academic year</label>
                    <select class="form-control" id="academic_year_id" name="academic_year_id">
                        <option value="">-- none --</option>
                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear->id }}" {{ (int) old('academic_year_id') === $academicYear->id ? 'selected' : '' }}>
                                {{ $academicYear->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="grade_id">Grade</label>
                    <select class="form-control" id="grade_id" name="grade_id">
                        <option value="">-- none --</option>
                        @foreach ($grades as $grade)
                            <option value="{{ $grade->id }}" {{ (int) old('grade_id') === $grade->id ? 'selected' : '' }}>
                                {{ $grade->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="section_id">Section</label>
                    <select class="form-control" id="section_id" name="section_id">
                        <option value="">-- none --</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" {{ (int) old('section_id') === $section->id ? 'selected' : '' }}>
                                {{ $section->grade?->name }} - {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn">Register student</button>
                <a class="btn btn-secondary" href="{{ route('families.show', $family) }}">Cancel</a>
            </div>
        </form>
    </x-card>
@endsection
