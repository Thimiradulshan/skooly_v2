<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('lets an admin create update and delete an unused subject', function () {
    $admin = adminUser();

    $this->actingAs($admin)->post(route('subjects.store'), [
        'code' => 'MATH', 'name' => 'Mathematics',
    ])->assertRedirect();

    $subject = Subject::query()->sole();

    $this->actingAs($admin)->put(route('subjects.update', $subject), [
        'code' => 'MATHS', 'name' => 'Advanced Mathematics',
    ])->assertRedirect(route('subjects.show', $subject));

    expect($subject->refresh()->code)->toBe('MATHS');

    $this->actingAs($admin)->delete(route('subjects.destroy', $subject))
        ->assertRedirect(route('subjects.index'));

    $this->assertModelMissing($subject);
});

it('blocks subject deletion while a teaching assignment references it', function () {
    $admin = adminUser();
    $teacher = userWithRole(Role::TEACHER);
    $subject = Subject::factory()->create();
    $teacher->qualifiedSubjects()->attach($subject);
    $assignment = TeacherAssignment::factory()->create([
        'academic_year_id' => AcademicYear::factory(),
        'section_id' => Section::factory()->for(Grade::factory()),
        'teacher_id' => $teacher,
        'subject_id' => $subject,
    ]);

    $this->actingAs($admin)->delete(route('subjects.destroy', $subject))
        ->assertSessionHasErrors('subject');

    $this->assertModelExists($subject);
    $this->assertModelExists($assignment);
});

it('blocks teachers and accountants from subject management', function () {
    $subject = Subject::factory()->create();

    $this->actingAs(userWithRole(Role::TEACHER))->get(route('subjects.index'))->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->get(route('subjects.show', $subject))->assertForbidden();
});

it('records qualifications before creating teaching and class-in-charge assignments', function () {
    $admin = adminUser();
    $teacher = userWithRole(Role::TEACHER);
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);
    $section = Section::factory()->for(Grade::factory())->create();
    $subject = Subject::factory()->create();

    $this->actingAs($admin)->post(route('teacher-qualifications.store'), [
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
    ])->assertRedirect(route('teacher-qualifications.index'));

    $this->actingAs($admin)->get(route('teacher-qualifications.show', $teacher))
        ->assertOk()
        ->assertSee($subject->name);

    $this->actingAs($admin)->post(route('teacher-assignments.store'), [
        'academic_year_id' => $academicYear->id,
        'section_id' => $section->id,
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('section-year-assignments.store'), [
        'academic_year_id' => $academicYear->id,
        'section_id' => $section->id,
        'class_in_charge_id' => $teacher->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('teacher_assignments', ['teacher_id' => $teacher->id, 'subject_id' => $subject->id]);
    $this->assertDatabaseHas('section_year_assignments', ['class_in_charge_id' => $teacher->id, 'section_id' => $section->id]);
});

it('requires a teaching qualification before assigning a teacher to a subject', function () {
    $teacher = userWithRole(Role::TEACHER);

    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);

    $this->actingAs(adminUser())->post(route('teacher-assignments.store'), [
        'academic_year_id' => $academicYear->id,
        'section_id' => Section::factory()->for(Grade::factory())->create()->id,
        'teacher_id' => $teacher->id,
        'subject_id' => Subject::factory()->create()->id,
    ])->assertSessionHasErrors('subject_id');

    expect(TeacherAssignment::query()->count())->toBe(0);
});
