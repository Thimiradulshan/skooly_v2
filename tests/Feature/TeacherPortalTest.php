<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function teacherPortalUser(): User
{
    $teacher = User::factory()->create(['email_verified_at' => now(), 'is_active' => true]);
    $teacher->roles()->attach(Role::query()->firstOrCreate(['name' => Role::TEACHER]));

    return $teacher;
}

it('denies guests and non-teachers the teacher portal', function () {
    $this->get(route('teacher.portal'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create(['email_verified_at' => now()]))->get(route('teacher.portal'))->assertForbidden();
});

it('shows only current active-year assignments for the authenticated teacher', function () {
    $year = AcademicYear::factory()->create(['name' => 'Current Year']);
    setActiveAcademicYear($year);
    $teacher = teacherPortalUser();
    $otherTeacher = teacherPortalUser();
    $grade = Grade::factory()->create(['name' => 'Grade 6']);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    $subject = Subject::factory()->create(['name' => 'Science']);
    TeacherAssignment::factory()->create(['academic_year_id' => $year->id, 'teacher_id' => $teacher->id, 'section_id' => $section->id, 'subject_id' => $subject->id]);
    TeacherAssignment::factory()->create(['academic_year_id' => $year->id, 'teacher_id' => $otherTeacher->id, 'section_id' => $section->id, 'subject_id' => Subject::factory()->create(['name' => 'Hidden Subject'])->id]);

    $this->actingAs($teacher)->get(route('teacher.portal'))
        ->assertOk()->assertSee('Current Year')->assertSee('Science')->assertDontSee('Hidden Subject');
});

it('rejects inactive teacher accounts even when they retain teacher role', function () {
    $teacher = teacherPortalUser();
    $teacher->update(['is_active' => false]);

    $this->actingAs($teacher)->get(route('teacher.portal'))->assertForbidden();
});

it('shows teacher classes and subjects only for the active year', function () {
    $year = AcademicYear::factory()->create(['name' => 'Active']);
    setActiveAcademicYear($year);
    $teacher = teacherPortalUser();
    $grade = Grade::factory()->create(['name' => 'Grade 4']);
    $section = Section::factory()->for($grade)->create(['name' => 'B']);
    TeacherAssignment::factory()->create(['academic_year_id' => $year->id, 'teacher_id' => $teacher->id, 'section_id' => $section->id, 'subject_id' => Subject::factory()->create(['name' => 'Math'])->id]);

    $this->actingAs($teacher)->get(route('teacher.classes'))
        ->assertOk()
        ->assertSee('Grade 4')
        ->assertSee('Math')
        ->assertSee(route('teacher.portal', [], false), false)
        ->assertDontSee(route('payments.collect', [], false), false);
    $this->actingAs($teacher)->get(route('teacher.subjects'))->assertOk()->assertSee('Math')->assertSee('Active');
});

it('lists only students enrolled in the teachers assigned active-year sections', function () {
    $year = AcademicYear::factory()->create();
    setActiveAcademicYear($year);
    $teacher = teacherPortalUser();
    $grade = Grade::factory()->create();
    $assignedSection = Section::factory()->for($grade)->create(['name' => 'Assigned']);
    $otherSection = Section::factory()->for($grade)->create(['name' => 'Other']);
    TeacherAssignment::factory()->create(['academic_year_id' => $year->id, 'teacher_id' => $teacher->id, 'section_id' => $assignedSection->id, 'subject_id' => Subject::factory()->create()->id]);
    $visible = Student::factory()->create(['name' => 'Visible Learner']);
    $hidden = Student::factory()->create(['name' => 'Hidden Learner']);
    Enrollment::factory()->create(['student_id' => $visible->id, 'academic_year_id' => $year->id, 'grade_id' => $grade->id, 'section_id' => $assignedSection->id]);
    Enrollment::factory()->create(['student_id' => $hidden->id, 'academic_year_id' => $year->id, 'grade_id' => $grade->id, 'section_id' => $otherSection->id]);

    $this->actingAs($teacher)->get(route('teacher.students', ['search' => 'Visible']))->assertOk()->assertSee('Visible Learner')->assertDontSee('Hidden Learner');
    $this->actingAs($teacher)->get(route('teacher.students.show', $hidden))->assertNotFound();
    $this->actingAs($teacher)->get(route('teacher.students.show', $visible))->assertOk()->assertSee('Visible Learner');
});
