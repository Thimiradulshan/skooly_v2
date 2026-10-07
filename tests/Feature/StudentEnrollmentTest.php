<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentPlacement;
use App\Models\Family;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

it('associates a student with a family', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();

    expect($student->family->is($family))->toBeTrue();
});

it('allows a family to have multiple students', function () {
    $family = Family::factory()->create();
    Student::factory()->count(2)->recycle($family)->create();

    expect($family->students)->toHaveCount(2);
});

it('defaults a student to pending registration', function () {
    $student = Student::factory()->create()->refresh();

    expect($student->status)->toBe(Student::STATUS_PENDING_REGISTRATION);
});

it('requires unique admission numbers', function () {
    Student::factory()->create(['admission_no' => 'ADM-001']);

    expect(fn () => Student::factory()->create(['admission_no' => 'ADM-001']))
        ->toThrow(QueryException::class);
});

it('allows one enrollment per academic year for a student', function () {
    $student = Student::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    Enrollment::factory()->for($student)->for($academicYear)->for($grade)->for($section)->create();

    expect(fn () => Enrollment::factory()->for($student)->for($academicYear)->for($grade)->for($section)->create())
        ->toThrow(QueryException::class);
});

it('stores the academic year grade and section on an enrollment', function () {
    $student = Student::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $enrollment = Enrollment::factory()
        ->for($student)
        ->for($academicYear)
        ->for($grade)
        ->for($section)
        ->create();

    expect($enrollment->student->is($student))->toBeTrue();
    expect($enrollment->academicYear->is($academicYear))->toBeTrue();
    expect($enrollment->grade->is($grade))->toBeTrue();
    expect($enrollment->section->is($section))->toBeTrue();
});

it('does not store a mutable grade or section on students', function () {
    expect(Schema::hasColumn('students', 'grade_id'))->toBeFalse();
    expect(Schema::hasColumn('students', 'section_id'))->toBeFalse();
});

it('retains the initial and changed placements for an enrollment', function () {
    $student = Student::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $firstGrade = Grade::factory()->create();
    $firstSection = Section::factory()->for($firstGrade)->create();
    $secondGrade = Grade::factory()->create();
    $secondSection = Section::factory()->for($secondGrade)->create();
    $enrollment = Enrollment::factory()
        ->for($student)
        ->for($academicYear)
        ->for($firstGrade)
        ->for($firstSection)
        ->create();

    setActiveAcademicYear($academicYear);

    $enrollment->placeIn($secondGrade, $secondSection);

    $placements = $enrollment->placements()->orderBy('id')->get();

    expect($enrollment->refresh()->grade->is($secondGrade))->toBeTrue();
    expect($enrollment->section->is($secondSection))->toBeTrue();
    expect($placements)->toHaveCount(2);
    expect($placements->first()->grade->is($firstGrade))->toBeTrue();
    expect($placements->first()->section->is($firstSection))->toBeTrue();
    expect($placements->last()->grade->is($secondGrade))->toBeTrue();
    expect($placements->last()->section->is($secondSection))->toBeTrue();
});

it('rolls back a placement change when the grade does not own the section', function () {
    $student = Student::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $enrollmentGrade = Grade::factory()->create();
    $enrollmentSection = Section::factory()->for($enrollmentGrade)->create();
    $otherGrade = Grade::factory()->create();
    $enrollment = Enrollment::factory()
        ->for($student)
        ->for($academicYear)
        ->for($enrollmentGrade)
        ->for($enrollmentSection)
        ->create();

    setActiveAcademicYear($academicYear);

    expect(fn () => $enrollment->placeIn($otherGrade, $enrollmentSection))
        ->toThrow(QueryException::class);

    expect($enrollment->refresh()->grade->is($enrollmentGrade))->toBeTrue();
    expect($enrollment->section->is($enrollmentSection))->toBeTrue();
    expect($enrollment->placements)->toBeEmpty();
});

it('rejects mismatched grades and sections for enrollments and placement history', function () {
    $student = Student::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $sectionGrade = Grade::factory()->create();
    $section = Section::factory()->for($sectionGrade)->create();
    $otherGrade = Grade::factory()->create();

    expect(fn () => Enrollment::factory()->for($student)->for($academicYear)->create([
        'grade_id' => $otherGrade->id,
        'section_id' => $section->id,
    ]))->toThrow(QueryException::class);

    $enrollment = Enrollment::factory()->for($student)->for($academicYear)->for($sectionGrade)->for($section)->create();

    expect(fn () => EnrollmentPlacement::factory()->for($enrollment)->create([
        'grade_id' => $otherGrade->id,
        'section_id' => $section->id,
    ]))->toThrow(QueryException::class);
});
