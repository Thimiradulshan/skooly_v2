<?php

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(adminUser());
    setActiveAcademicYear(AcademicYear::factory()->create());
});

it('loads the families index page', function () {
    Family::factory()->create(['family_code' => 'FAM-INDEX']);

    $this->get(route('families.index'))
        ->assertOk()
        ->assertSee('FAM-INDEX');
});

it('keeps student registration discoverable from the student directory', function () {
    $this->get(route('students.index'))
        ->assertOk()
        ->assertSee('Register student')
        ->assertSee(route('families.index'), false);
});

it('stores a validated student photo outside the public disk', function () {
    Storage::fake('local');
    $family = Family::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();

    $this->post(route('families.students.store', $family), [
        'name' => 'Photo Student',
        'admission_no' => 'PHOTO-001',
        'dob' => '2015-01-01',
        'gender' => 'female',
        'photo' => UploadedFile::fake()->image('student.jpg', 300, 300),
        'academic_year_id' => $academicYear->id,
        'grade_id' => $grade->id,
        'section_id' => $section->id,
    ])->assertRedirect(route('families.show', $family));

    $student = Student::query()->sole();
    expect($student->photo_path)->toStartWith('student-photos/');
    Storage::disk('local')->assertExists($student->photo_path);
});

it('renders a stored student photo through an admin-only route', function () {
    Storage::fake('local');
    $student = Student::factory()->for(Family::factory())->create([
        'photo_path' => UploadedFile::fake()->image('student.png')->store('student-photos', 'local'),
    ]);

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->get(route('students.photo', $student))->assertRedirect(route('login'));
    $this->actingAs(userWithRole(Role::TEACHER))->get(route('students.photo', $student))->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->get(route('students.photo', $student))->assertForbidden();
    $photoResponse = $this->actingAs(adminUser())->get(route('students.photo', $student));
    $photoResponse->assertOk();
    expect((string) $photoResponse->headers->get('Content-Disposition'))->toStartWith('inline;');
    $this->actingAs(adminUser())->get(route('students.show', $student))
        ->assertSee(route('students.photo', $student, false), false)
        ->assertSee('Stored privately');
});

it('searches families by guardian name contact number and NIC', function () {
    $family = Family::factory()->create(['family_code' => 'FAM-GUARDIAN-SEARCH']);
    Guardian::factory()->for($family)->create([
        'name' => 'Searchable Guardian',
        'contact_no' => '0771234567',
        'nic' => 'NIC-SEARCH-001',
    ]);
    Family::factory()->create(['family_code' => 'FAM-UNRELATED']);

    foreach (['Searchable Guardian', '0771234567', 'NIC-SEARCH-001'] as $search) {
        $this->get(route('families.index', ['search' => $search]))
            ->assertOk()
            ->assertSee('FAM-GUARDIAN-SEARCH')
            ->assertDontSee('FAM-UNRELATED');
    }
});

it('loads the family create page', function () {
    $this->get(route('families.create'))->assertOk();
});

it('stores a family through the web route and audits it', function () {
    $this->post(route('families.store'), [
        'family_code' => 'FAM-WEB-1',
        'address' => '12 Main Street',
        'home_contact_no' => '0770000001',
        'combined_billing_enabled' => '1',
    ])->assertRedirect();

    $family = Family::query()->sole();

    expect($family->family_code)->toBe('FAM-WEB-1');
    expect($family->combined_billing_enabled)->toBeTrue();
    expect(AuditLog::query()->where('action', AuditLog::ACTION_FAMILY_CREATED)->count())->toBe(1);
});

it('stores a family with one guardian through the web route', function () {
    $this->post(route('families.store'), [
        'family_code' => 'FAM-WEB-2',
        'combined_billing_enabled' => '1',
        'guardians' => [
            ['name' => 'Web Parent', 'relationship' => 'mother', 'email' => 'parent@example.test'],
        ],
    ])->assertRedirect();

    $family = Family::query()->with('guardians')->sole();

    expect($family->guardians)->toHaveCount(1);
    expect($family->guardians->sole()->name)->toBe('Web Parent');
    expect($family->students)->toHaveCount(0);
});

it('displays the family and guardian on the show page', function () {
    $family = Family::factory()->create(['family_code' => 'FAM-SHOW']);
    Guardian::factory()->for($family)->create(['name' => 'Shown Guardian']);

    $this->get(route('families.show', $family))
        ->assertOk()
        ->assertSee('FAM-SHOW')
        ->assertSee('Shown Guardian')
        ->assertSee('Register student');
});

it('updates allowed family fields through the web route', function () {
    $family = Family::factory()->create([
        'family_code' => 'FAM-EDIT',
        'address' => 'Old address',
        'combined_billing_enabled' => true,
    ]);

    $this->put(route('families.update', $family), [
        'family_code' => 'FAM-EDITED',
        'address' => 'New address',
        'combined_billing_enabled' => '1',
    ])->assertRedirect(route('families.show', $family));

    $family->refresh();

    expect($family->family_code)->toBe('FAM-EDITED');
    expect($family->address)->toBe('New address');
    expect($family->combined_billing_enabled)->toBeTrue();
    expect(AuditLog::query()->where('action', AuditLog::ACTION_FAMILY_UPDATED)->count())->toBe(1);
});

it('rejects a duplicate family code', function () {
    Family::factory()->create(['family_code' => 'FAM-TAKEN']);

    $this->post(route('families.store'), ['family_code' => 'FAM-TAKEN'])
        ->assertSessionHasErrors('family_code');

    expect(Family::count())->toBe(1);
});

it('loads the student registration page for a family', function () {
    $family = Family::factory()->create(['family_code' => 'FAM-REG']);
    Guardian::factory()->for($family)->create(['name' => 'Reg Parent']);
    $academicYear = AcademicYear::factory()->create(['name' => '2026/2027']);
    setActiveAcademicYear($academicYear);
    Grade::factory()->create(['name' => 'Grade 1']);
    Section::factory()->create(['name' => 'A']);

    $this->get(route('families.students.create', $family))
        ->assertOk()
        ->assertSee('Reg Parent')
        ->assertSee('2026/2027')
        ->assertSee('Grade 1');
});

it('registers a pending_registration student through the web route', function () {
    $family = Family::factory()->create();

    $this->post(route('families.students.store', $family), [
        'name' => 'Web Student',
        'dob' => '2015-04-05',
        'gender' => 'female',
        'admission_no' => 'ADM-WEB-1',
    ])->assertRedirect(route('families.show', $family));

    $student = Student::query()->sole();

    expect($student->family_id)->toBe($family->id);
    expect($student->status)->toBe(Student::STATUS_PENDING_REGISTRATION);
    expect(AuditLog::query()->where('action', AuditLog::ACTION_STUDENT_REGISTERED)->count())->toBe(1);
});

it('prevents a registration request from bypassing pending registration status', function () {
    $family = Family::factory()->create();

    $this->post(route('families.students.store', $family), [
        'name' => 'Still Pending',
        'dob' => '2015-04-05',
        'gender' => 'female',
        'admission_no' => 'ADM-WEB-PENDING',
        'status' => Student::STATUS_ACTIVE,
    ])->assertRedirect(route('families.show', $family));

    expect(Student::query()->sole()->status)->toBe(Student::STATUS_PENDING_REGISTRATION);
});

it('prevents a pending student from being manually activated through the profile update', function () {
    $student = Student::factory()->for(Family::factory())->create([
        'status' => Student::STATUS_PENDING_REGISTRATION,
    ]);

    $this->put(route('students.update', $student), [
        'name' => $student->name,
        'dob' => $student->dob->toDateString(),
        'gender' => $student->gender,
        'admission_no' => $student->admission_no,
        'status' => Student::STATUS_ACTIVE,
    ])->assertSessionHasErrors('status');

    expect($student->refresh()->status)->toBe(Student::STATUS_PENDING_REGISTRATION);
});

it('links only the guardians selected on the web form', function () {
    $family = Family::factory()->create();
    $selectedGuardian = Guardian::factory()->for($family)->create();
    $unselectedGuardian = Guardian::factory()->for($family)->create();

    $this->post(route('families.students.store', $family), [
        'name' => 'Selected Child',
        'dob' => '2015-04-05',
        'gender' => 'male',
        'admission_no' => 'ADM-WEB-2',
        'guardian_ids' => [$selectedGuardian->id],
    ])->assertRedirect();

    $student = Student::query()->sole();

    expect($student->guardians)->toHaveCount(1);
    expect($student->guardians->sole()->is($selectedGuardian))->toBeTrue();
    expect($unselectedGuardian->students)->toBeEmpty();
});

it('creates an initial enrollment from the web form', function () {
    $family = Family::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();

    $this->post(route('families.students.store', $family), [
        'name' => 'Enrolled Child',
        'dob' => '2015-04-05',
        'gender' => 'female',
        'admission_no' => 'ADM-WEB-3',
        'academic_year_id' => $academicYear->id,
        'grade_id' => $grade->id,
        'section_id' => $section->id,
    ])->assertRedirect();

    $enrollment = Enrollment::query()->sole();

    expect($enrollment->academic_year_id)->toBe($academicYear->id);
    expect($enrollment->grade_id)->toBe($grade->id);
    expect($enrollment->section_id)->toBe($section->id);
});

it('fails safely when the web enrollment section does not belong to the grade', function () {
    $family = Family::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $sectionOfOtherGrade = Section::factory()->for(Grade::factory())->create();

    $this->post(route('families.students.store', $family), [
        'name' => 'Bad Enrollment',
        'dob' => '2015-04-05',
        'gender' => 'female',
        'admission_no' => 'ADM-WEB-4',
        'academic_year_id' => $academicYear->id,
        'grade_id' => $grade->id,
        'section_id' => $sectionOfOtherGrade->id,
    ]);

    expect(Student::count())->toBe(0);
    expect(Enrollment::count())->toBe(0);
});

it('requires all three enrollment fields together', function () {
    $family = Family::factory()->create();
    $grade = Grade::factory()->create();

    $this->post(route('families.students.store', $family), [
        'name' => 'Partial Enrollment',
        'dob' => '2015-04-05',
        'gender' => 'female',
        'admission_no' => 'ADM-WEB-5',
        'grade_id' => $grade->id,
    ])->assertSessionHasErrors('section_id');

    expect(Student::count())->toBe(0);
});

it('rejects a duplicate admission number', function () {
    $family = Family::factory()->create();
    Student::factory()->create(['admission_no' => 'ADM-TAKEN']);

    $this->post(route('families.students.store', $family), [
        'name' => 'Duplicate Admission',
        'dob' => '2015-04-05',
        'gender' => 'female',
        'admission_no' => 'ADM-TAKEN',
    ])->assertSessionHasErrors('admission_no');

    expect(Student::count())->toBe(1);
});

it('does not create due items when registering a student', function () {
    $family = Family::factory()->create();

    $this->post(route('families.students.store', $family), [
        'name' => 'No Dues Child',
        'dob' => '2015-04-05',
        'gender' => 'female',
        'admission_no' => 'ADM-WEB-6',
    ])->assertRedirect();

    expect(StudentDueItem::count())->toBe(0);
    expect(Student::query()->sole()->status)->toBe(Student::STATUS_PENDING_REGISTRATION);
});

it('does not expose any delete route for families or students', function () {
    expect(Route::has('families.destroy'))->toBeFalse();
    expect(Route::has('families.students.destroy'))->toBeFalse();

    $family = Family::factory()->create();

    $this->delete(route('families.show', $family))->assertMethodNotAllowed();
});
