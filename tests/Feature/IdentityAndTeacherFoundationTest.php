<?php

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Section;
use App\Models\SectionYearAssignment;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('allows a user to hold multiple supported roles', function () {
    $user = User::factory()->create();
    $adminRole = Role::factory()->admin()->create();
    $accountantRole = Role::factory()->accountant()->create();

    $user->roles()->attach([$adminRole->id, $accountantRole->id]);

    expect($user->roles()->pluck('name')->all())
        ->toEqualCanonicalizing([Role::ADMIN, Role::ACCOUNTANT]);
    expect($user->hasRole(Role::ADMIN))->toBeTrue();
    expect($user->hasRole(Role::ACCOUNTANT))->toBeTrue();
    expect($user->hasRole(Role::TEACHER))->toBeFalse();
});

it('keeps subject qualifications separate from yearly teaching assignments', function () {
    $teacher = User::factory()->teacher()->create();
    $subject = Subject::factory()->create();

    $teacher->qualifiedSubjects()->attach($subject);

    expect($teacher->qualifiedSubjects->sole()->is($subject))->toBeTrue();
    expect($subject->qualifiedTeachers->sole()->is($teacher))->toBeTrue();
    expect($teacher->teacherAssignments)->toBeEmpty();
});

it('requires subject qualification before a yearly teaching assignment', function () {
    $academicYear = AcademicYear::factory()->create();
    $section = Section::factory()->create();
    $teacher = User::factory()->teacher()->create();
    $subject = Subject::factory()->create();

    expect(fn () => TeacherAssignment::query()->create([
        'academic_year_id' => $academicYear->id,
        'section_id' => $section->id,
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
    ]))->toThrow(QueryException::class);

    $teacher->qualifiedSubjects()->attach($subject);
    $assignment = TeacherAssignment::query()->create([
        'academic_year_id' => $academicYear->id,
        'section_id' => $section->id,
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
    ]);

    expect($assignment->teacher->is($teacher))->toBeTrue();
    expect($assignment->subject->is($subject))->toBeTrue();
    expect($assignment->academicYear->is($academicYear))->toBeTrue();
    expect($assignment->section->is($section))->toBeTrue();
});

it('scopes teaching assignments by academic year and prevents duplicates', function () {
    $firstYear = AcademicYear::factory()->create();
    $secondYear = AcademicYear::factory()->create();
    $section = Section::factory()->create();
    $teacher = User::factory()->teacher()->create();
    $subject = Subject::factory()->create();
    $teacher->qualifiedSubjects()->attach($subject);
    $attributes = [
        'section_id' => $section->id,
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
    ];

    TeacherAssignment::query()->create([
        ...$attributes,
        'academic_year_id' => $firstYear->id,
    ]);
    TeacherAssignment::query()->create([
        ...$attributes,
        'academic_year_id' => $secondYear->id,
    ]);

    expect($teacher->teacherAssignments)->toHaveCount(2);
    expect(fn () => TeacherAssignment::query()->create([
        ...$attributes,
        'academic_year_id' => $firstYear->id,
    ]))->toThrow(QueryException::class);
});

it('assigns one class teacher per section in each academic year', function () {
    $firstYear = AcademicYear::factory()->create();
    $secondYear = AcademicYear::factory()->create();
    $section = Section::factory()->create();
    $firstTeacher = User::factory()->teacher()->create();
    $secondTeacher = User::factory()->teacher()->create();

    $firstAssignment = SectionYearAssignment::query()->create([
        'academic_year_id' => $firstYear->id,
        'section_id' => $section->id,
        'class_in_charge_id' => $firstTeacher->id,
    ]);
    SectionYearAssignment::query()->create([
        'academic_year_id' => $secondYear->id,
        'section_id' => $section->id,
        'class_in_charge_id' => $secondTeacher->id,
    ]);

    expect($firstAssignment->classInCharge->is($firstTeacher))->toBeTrue();
    expect($section->yearAssignments)->toHaveCount(2);
    expect($firstYear->sectionYearAssignments)->toHaveCount(1);
    expect(fn () => SectionYearAssignment::query()->create([
        'academic_year_id' => $firstYear->id,
        'section_id' => $section->id,
        'class_in_charge_id' => $secondTeacher->id,
    ]))->toThrow(QueryException::class);
});
