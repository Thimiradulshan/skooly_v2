<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('records placement history instead of overwriting an enrollment', function () {
    $student = Student::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $firstGrade = Grade::factory()->create();
    $firstSection = Section::factory()->for($firstGrade)->create();
    $secondGrade = Grade::factory()->create();
    $secondSection = Section::factory()->for($secondGrade)->create();
    $enrollment = Enrollment::factory()->for($student)->for($academicYear)->for($firstGrade)->for($firstSection)->create();
    setActiveAcademicYear($academicYear);

    $this->actingAs(adminUser())->post(route('enrollments.placements.store', $enrollment), [
        'grade_id' => $secondGrade->id,
        'section_id' => $secondSection->id,
    ])->assertRedirect(route('students.show', $student));

    expect($enrollment->refresh()->grade->is($secondGrade))->toBeTrue();
    expect($enrollment->section->is($secondSection))->toBeTrue();
    expect($enrollment->placements)->toHaveCount(2);
    expect($enrollment->placements->first()->section_id)->toBe($firstSection->id);
});

it('rejects a placement with a section from another grade', function () {
    $student = Student::factory()->create();
    $enrollment = Enrollment::factory()->for($student)->create();
    setActiveAcademicYear($enrollment->academicYear);
    $otherSection = Section::factory()->for(Grade::factory())->create();

    $this->actingAs(adminUser())->post(route('enrollments.placements.store', $enrollment), [
        'grade_id' => $enrollment->grade_id,
        'section_id' => $otherSection->id,
    ])->assertSessionHasErrors('section_id');

    expect($enrollment->refresh()->section_id)->not->toBe($otherSection->id);
});

it('blocks teachers and accountants from placement management', function () {
    $enrollment = Enrollment::factory()->create();
    setActiveAcademicYear($enrollment->academicYear);

    $this->actingAs(userWithRole(Role::TEACHER))->get(route('enrollments.placements.create', $enrollment))->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->post(route('enrollments.placements.store', $enrollment), [])->assertForbidden();

});
