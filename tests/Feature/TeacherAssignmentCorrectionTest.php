<?php

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

function correctableTeacherAssignment(): array
{
    $year = AcademicYear::factory()->create();
    setActiveAcademicYear($year);
    $section = Section::factory()->for(Grade::factory())->create();
    $subject = Subject::factory()->create();
    $teacher = userWithRole(Role::TEACHER);
    $teacher->qualifiedSubjects()->attach($subject);
    $assignment = TeacherAssignment::factory()->create(['academic_year_id' => $year->id, 'section_id' => $section->id, 'teacher_id' => $teacher->id, 'subject_id' => $subject->id]);

    return compact('year', 'section', 'subject', 'teacher', 'assignment');
}

it('allows an admin to correct a current assignment and records the change', function () {
    ['assignment' => $assignment] = correctableTeacherAssignment();
    $admin = adminUser();
    $subject = Subject::factory()->create();
    $teacher = userWithRole(Role::TEACHER);
    $teacher->qualifiedSubjects()->attach($subject);

    $this->actingAs($admin)->get(route('teacher-assignments.edit', $assignment))->assertOk()->assertSee('Stored placement');
    $this->actingAs($admin)->put(route('teacher-assignments.update', $assignment), ['teacher_id' => $teacher->id, 'subject_id' => $subject->id])
        ->assertRedirect(route('teacher-assignments.index'));

    $this->assertDatabaseHas('teacher_assignments', ['id' => $assignment->id, 'teacher_id' => $teacher->id, 'subject_id' => $subject->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ACTION_TEACHER_ASSIGNMENT_UPDATED, 'actor_user_id' => $admin->id, 'auditable_id' => $assignment->id]);
});

it('rejects unqualified teachers and duplicate corrections', function () {
    ['year' => $year, 'section' => $section, 'assignment' => $assignment] = correctableTeacherAssignment();
    $unqualified = userWithRole(Role::TEACHER);
    $subject = Subject::factory()->create();

    $this->actingAs(adminUser())->put(route('teacher-assignments.update', $assignment), ['teacher_id' => $unqualified->id, 'subject_id' => $subject->id])->assertSessionHasErrors('teacher_id');

    $qualified = userWithRole(Role::TEACHER);
    $qualified->qualifiedSubjects()->attach($subject);
    TeacherAssignment::factory()->create(['academic_year_id' => $year->id, 'section_id' => $section->id, 'teacher_id' => $qualified->id, 'subject_id' => $subject->id]);
    $this->actingAs(adminUser())->put(route('teacher-assignments.update', $assignment), ['teacher_id' => $qualified->id, 'subject_id' => $subject->id])->assertSessionHasErrors('subject_id');
});

it('protects historical and archived assignments and denies non-admins', function () {
    ['assignment' => $assignment, 'year' => $year, 'section' => $section] = correctableTeacherAssignment();
    $otherYear = AcademicYear::factory()->create();
    setActiveAcademicYear($otherYear);

    $this->actingAs(adminUser())->get(route('teacher-assignments.edit', $assignment))->assertForbidden();
    $this->actingAs(userWithRole(Role::TEACHER))->put(route('teacher-assignments.update', $assignment), [])->assertForbidden();
    expect(Route::has('teacher-assignments.destroy'))->toBeFalse();

    $year->update(['is_archived' => true]);
    $this->actingAs(adminUser())->get(route('teacher-assignments.edit', $assignment))->assertForbidden();
});
