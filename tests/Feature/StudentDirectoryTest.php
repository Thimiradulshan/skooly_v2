<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('lets an admin browse the student directory with family and guardian navigation', function () {
    $academicYear = AcademicYear::factory()->create(['name' => '2026 Academic Year']);
    setActiveAcademicYear($academicYear);
    $grade = Grade::factory()->create(['name' => 'Grade 5']);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    $family = Family::factory()->create(['family_code' => 'DIRECTORY-FAMILY']);
    $student = Student::factory()->for($family)->create(['name' => 'Directory Student', 'admission_no' => 'ADM-DIRECTORY']);
    $guardian = Guardian::factory()->for($family)->create(['name' => 'Linked Guardian']);
    $unlinkedGuardian = Guardian::factory()->for($family)->create(['name' => 'Unlinked Guardian']);
    $student->guardians()->attach($guardian);
    Enrollment::factory()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'grade_id' => $grade->id,
        'section_id' => $section->id,
    ]);

    $this->actingAs(adminUser())
        ->get(route('students.index'))
        ->assertOk()
        ->assertSee('Directory Student')
        ->assertSee(route('students.show', $student), false)
        ->assertSee('ADM-DIRECTORY')
        ->assertSee('Grade 5 A')
        ->assertSee('DIRECTORY-FAMILY')
        ->assertSee(route('families.show', $family), false)
        ->assertSee('Linked Guardian')
        ->assertSee(route('guardians.show', $guardian), false)
        ->assertDontSee('Unlinked Guardian');
});

it('denies the student directory to guests and unauthorized roles', function () {
    $this->get(route('students.index'))->assertRedirect(route('login'));

    $this->actingAs(userWithRole(Role::TEACHER))->get(route('students.index'))->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->get(route('students.index'))->assertForbidden();
});

it('searches students by name, admission number, family code, and linked guardian name', function () {
    $family = Family::factory()->create(['family_code' => 'FAMILY-SEARCH-001']);
    $student = Student::factory()->for($family)->create(['name' => 'Asha Searchable', 'admission_no' => 'ADM-SEARCH-001']);
    $guardian = Guardian::factory()->for($family)->create(['name' => 'Nimal Guardian Search']);
    $student->guardians()->attach($guardian);
    Student::factory()->create(['name' => 'Unrelated Student', 'admission_no' => 'ADM-UNRELATED']);

    foreach (['Asha Searchable', 'ADM-SEARCH-001', 'FAMILY-SEARCH-001', 'Nimal Guardian Search'] as $search) {
        $this->actingAs(adminUser())
            ->get(route('students.index', ['search' => $search]))
            ->assertOk()
            ->assertSee('Asha Searchable')
            ->assertDontSee('Unrelated Student');
    }
});

it('filters students by year, grade, section, and status together', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $matchingStudent = Student::factory()->create(['name' => 'Matching Student', 'status' => Student::STATUS_ACTIVE]);
    $differentStatusStudent = Student::factory()->create(['name' => 'Different Status Student', 'status' => Student::STATUS_INACTIVE]);
    $differentSectionStudent = Student::factory()->create(['name' => 'Different Section Student', 'status' => Student::STATUS_ACTIVE]);
    $otherSection = Section::factory()->for($grade)->create();

    foreach ([$matchingStudent, $differentStatusStudent] as $student) {
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
        ]);
    }

    Enrollment::factory()->create([
        'student_id' => $differentSectionStudent->id,
        'academic_year_id' => $academicYear->id,
        'grade_id' => $grade->id,
        'section_id' => $otherSection->id,
    ]);

    $this->actingAs(adminUser())
        ->get(route('students.index', [
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'status' => Student::STATUS_ACTIVE,
        ]))
        ->assertOk()
        ->assertSee('Matching Student')
        ->assertDontSee('Different Status Student')
        ->assertDontSee('Different Section Student');
});

it('preserves directory filters across pagination', function () {
    foreach (range(1, 21) as $number) {
        Student::factory()->create([
            'name' => 'Paged Student '.$number,
            'admission_no' => 'ADM-PAGED-'.$number,
            'status' => Student::STATUS_ACTIVE,
        ]);
    }

    $response = $this->actingAs(adminUser())
        ->get(route('students.index', ['search' => 'Paged Student', 'status' => Student::STATUS_ACTIVE]));

    $response->assertOk()
        ->assertViewHas('students', fn ($students) => $students->perPage() === 20 && $students->total() === 21)
        ->assertSee('search=Paged%20Student&amp;status=active&amp;page=2', false);

    $this->actingAs(adminUser())
        ->get(route('students.index', ['search' => 'Paged Student', 'status' => Student::STATUS_ACTIVE, 'page' => 2]))
        ->assertOk()
        ->assertViewHas('students', fn ($students) => $students->currentPage() === 2 && $students->count() === 1);
});

it('shows a clear empty result state', function () {
    $this->actingAs(adminUser())
        ->get(route('students.index', ['search' => 'No such student']))
        ->assertOk()
        ->assertSee('No students match these filters')
        ->assertSee(route('students.index'), false);
});

it('uses the selected historical enrollment instead of the current enrollment', function () {
    $currentYear = AcademicYear::factory()->create(['name' => 'Current Year']);
    $historicalYear = AcademicYear::factory()->create(['name' => 'Historical Year']);
    setActiveAcademicYear($currentYear);
    $currentGrade = Grade::factory()->create(['name' => 'Current Grade']);
    $historicalGrade = Grade::factory()->create(['name' => 'Historical Grade']);
    $currentSection = Section::factory()->for($currentGrade)->create(['name' => 'Current Section']);
    $historicalSection = Section::factory()->for($historicalGrade)->create(['name' => 'Historical Section']);
    $student = Student::factory()->create(['name' => 'Historical Placement Student']);

    Enrollment::factory()->create([
        'student_id' => $student->id,
        'academic_year_id' => $currentYear->id,
        'grade_id' => $currentGrade->id,
        'section_id' => $currentSection->id,
    ]);
    Enrollment::factory()->create([
        'student_id' => $student->id,
        'academic_year_id' => $historicalYear->id,
        'grade_id' => $historicalGrade->id,
        'section_id' => $historicalSection->id,
    ]);

    $this->actingAs(adminUser())
        ->get(route('students.index', ['academic_year_id' => $historicalYear->id]))
        ->assertOk()
        ->assertSee('Historical Grade Historical Section')
        ->assertDontSee('Current Grade Current Section');
});

it('requires an academic year for grade and section filters', function () {
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();

    $this->actingAs(adminUser())
        ->get(route('students.index', ['grade_id' => $grade->id, 'section_id' => $section->id]))
        ->assertSessionHasErrors(['grade_id', 'section_id']);
});
