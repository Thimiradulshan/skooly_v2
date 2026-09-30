@extends('layouts.app')

@section('title', 'Register student')
@section('heading', 'Register student for '.$family->family_code)

@section('content')
    <form method="POST" action="{{ route('families.students.store', $family) }}">
        @csrf

        <label for="name">Student name</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required>

        <label for="dob">Date of birth</label>
        <input type="date" id="dob" name="dob" value="{{ old('dob') }}" required>

        <label for="gender">Gender</label>
        <input type="text" id="gender" name="gender" value="{{ old('gender') }}" required>

        <label for="admission_no">Admission number</label>
        <input type="text" id="admission_no" name="admission_no" value="{{ old('admission_no') }}" required>

        <label for="photo_path">Photo path</label>
        <input type="text" id="photo_path" name="photo_path" value="{{ old('photo_path') }}">

        <h2>Guardians (optional)</h2>
        <p>Only selected guardians may access this student.</p>

        @forelse ($family->guardians as $guardian)
            <label for="guardian_{{ $guardian->id }}">
                <input type="checkbox" id="guardian_{{ $guardian->id }}" name="guardian_ids[]"
                       value="{{ $guardian->id }}"
                       {{ in_array($guardian->id, (array) old('guardian_ids', [])) ? 'checked' : '' }}>
                {{ $guardian->name }}
            </label>
        @empty
            <p>This family has no guardians yet.</p>
        @endforelse

        <h2>Initial enrollment (optional)</h2>
        <p>Provide all three fields to create an enrollment, or leave all three blank to skip it.</p>

        <label for="academic_year_id">Academic year</label>
        <select id="academic_year_id" name="academic_year_id">
            <option value="">-- none --</option>
            @foreach ($academicYears as $academicYear)
                <option value="{{ $academicYear->id }}"
                    {{ (int) old('academic_year_id') === $academicYear->id ? 'selected' : '' }}>
                    {{ $academicYear->name }}
                </option>
            @endforeach
        </select>

        <label for="grade_id">Grade</label>
        <select id="grade_id" name="grade_id">
            <option value="">-- none --</option>
            @foreach ($grades as $grade)
                <option value="{{ $grade->id }}" {{ (int) old('grade_id') === $grade->id ? 'selected' : '' }}>
                    {{ $grade->name }}
                </option>
            @endforeach
        </select>

        <label for="section_id">Section</label>
        <select id="section_id" name="section_id">
            <option value="">-- none --</option>
            @foreach ($sections as $section)
                <option value="{{ $section->id }}" {{ (int) old('section_id') === $section->id ? 'selected' : '' }}>
                    {{ $section->grade?->name }} - {{ $section->name }}
                </option>
            @endforeach
        </select>

        <p>
            <button type="submit">Register student</button>
            <a href="{{ route('families.show', $family) }}">Cancel</a>
        </p>
    </form>
@endsection
