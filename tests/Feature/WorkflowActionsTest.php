<?php

use App\Actions\Discounts\ApplyStudentDiscount;
use App\Actions\Families\CreateFamily;
use App\Actions\Families\UpdateFamily;
use App\Actions\Fees\CreateFeeStructure;
use App\Actions\Guardians\LinkGuardianToStudent;
use App\Actions\Students\ActivateStudentAfterRegistrationPaid;
use App\Actions\Students\RegisterStudent;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDueItem;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use InvalidArgumentException;

uses(LazilyRefreshDatabase::class);

function familyPayload(array $overrides = []): array
{
    return array_merge([
        'family_code' => 'FAM-'.fake()->unique()->numerify('####'),
        'address' => '10 Test Road',
        'home_contact_no' => '0771234567',
    ], $overrides);
}

function studentPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test Child',
        'dob' => '2015-05-04',
        'gender' => 'female',
        'admission_no' => 'ADM-'.fake()->unique()->numerify('#####'),
    ], $overrides);
}

it('creates a family and audits family_created', function () {
    $actor = User::factory()->create();

    $family = app(CreateFamily::class)->handle(familyPayload(), [], $actor);

    $log = AuditLog::query()->where('action', AuditLog::ACTION_FAMILY_CREATED)->sole();

    expect($family->family_code)->toStartWith('FAM-');
    expect($family->combined_billing_enabled)->toBeTrue();
    expect($log->actor->is($actor))->toBeTrue();
    expect($log->auditable->is($family))->toBeTrue();
    expect($log->metadata['family_id'])->toBe($family->id);
});

it('creates guardians for a new family without linking students', function () {
    $family = app(CreateFamily::class)->handle(familyPayload(), [
        ['name' => 'Parent One', 'relationship' => 'mother'],
        ['name' => 'Parent Two', 'relationship' => 'father'],
    ]);

    expect($family->guardians)->toHaveCount(2);
    expect($family->students)->toHaveCount(0);
    expect($family->guardians->pluck('students')->flatten())->toHaveCount(0);
});

it('updates only allowed family fields and audits before and after', function () {
    $actor = User::factory()->create();
    $family = Family::factory()->create([
        'family_code' => 'FAM-BEFORE',
        'address' => 'Old address',
        'combined_billing_enabled' => true,
    ]);

    $updated = app(UpdateFamily::class)->handle($family, [
        'address' => 'New address',
        'combined_billing_enabled' => false,
        'id' => 999,
    ], $actor);

    $log = AuditLog::query()->where('action', AuditLog::ACTION_FAMILY_UPDATED)->sole();

    expect($updated->address)->toBe('New address');
    expect($updated->combined_billing_enabled)->toBeFalse();
    expect($updated->family_code)->toBe('FAM-BEFORE');
    expect($updated->id)->toBe($family->id);
    expect($log->metadata['before']['address'])->toBe('Old address');
    expect($log->metadata['after']['address'])->toBe('New address');
    expect($log->metadata['after']['combined_billing_enabled'])->toBeFalse();
});

it('links a guardian to a student explicitly', function () {
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();
    $student = Student::factory()->for($family)->create();

    $linked = app(LinkGuardianToStudent::class)->handle($guardian, $student);

    expect($linked)->toBeTrue();
    expect($guardian->students()->whereKey($student->id)->exists())->toBeTrue();
});

it('links a guardian idempotently', function () {
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();
    $student = Student::factory()->for($family)->create();

    expect(app(LinkGuardianToStudent::class)->handle($guardian, $student))->toBeTrue();
    expect(app(LinkGuardianToStudent::class)->handle($guardian, $student))->toBeFalse();
    expect(app(LinkGuardianToStudent::class)->handle($guardian, $student))->toBeFalse();
    expect($guardian->students()->count())->toBe(1);
});

it('rejects linking a guardian and student from different families', function () {
    $guardian = Guardian::factory()->for(Family::factory())->create();
    $student = Student::factory()->for(Family::factory())->create();

    expect(fn () => app(LinkGuardianToStudent::class)->handle($guardian, $student))
        ->toThrow(InvalidArgumentException::class);

    expect($guardian->students)->toBeEmpty();
});

it('registers a pending_registration student under a family', function () {
    $actor = User::factory()->create();
    $family = Family::factory()->create();

    $student = app(RegisterStudent::class)->handle($family, studentPayload(), [], null, $actor);

    expect($student->family->is($family))->toBeTrue();
    expect($student->status)->toBe(Student::STATUS_PENDING_REGISTRATION);
    expect($student->admission_no)->toStartWith('ADM-');
});

it('rejects registration without an admission number or with an unknown status', function () {
    $family = Family::factory()->create();

    expect(fn () => app(RegisterStudent::class)->handle($family, studentPayload(['admission_no' => ''])))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => app(RegisterStudent::class)->handle($family, studentPayload(['status' => 'ghost'])))
        ->toThrow(InvalidArgumentException::class);

    expect($family->students)->toHaveCount(0);
});

it('links only the guardians explicitly provided at registration', function () {
    $family = Family::factory()->create();
    $linkedGuardian = Guardian::factory()->for($family)->create();
    $unlinkedGuardian = Guardian::factory()->for($family)->create();

    $student = app(RegisterStudent::class)->handle(
        $family,
        studentPayload(),
        [$linkedGuardian->id],
    );

    expect($student->guardians)->toHaveCount(1);
    expect($student->guardians->sole()->is($linkedGuardian))->toBeTrue();
    expect($unlinkedGuardian->students)->toBeEmpty();
    expect($unlinkedGuardian->paymentReminders)->toBeEmpty();
});

it('creates an initial enrollment when valid year grade and section are provided', function () {
    $family = Family::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    setActiveAcademicYear($academicYear);

    $student = app(RegisterStudent::class)->handle($family, studentPayload(), [], [
        'academic_year_id' => $academicYear->id,
        'grade_id' => $grade->id,
        'section_id' => $section->id,
    ]);

    $enrollment = Enrollment::query()->sole();

    expect($enrollment->student_id)->toBe($student->id);
    expect($enrollment->academic_year_id)->toBe($academicYear->id);
    expect($enrollment->grade_id)->toBe($grade->id);
    expect($enrollment->section_id)->toBe($section->id);
});

it('rejects registration when the section does not belong to the grade', function () {
    $family = Family::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $otherSection = Section::factory()->for(Grade::factory())->create();
    setActiveAcademicYear($academicYear);

    expect(fn () => app(RegisterStudent::class)->handle($family, studentPayload(), [], [
        'academic_year_id' => $academicYear->id,
        'grade_id' => $grade->id,
        'section_id' => $otherSection->id,
    ]))->toThrow(InvalidArgumentException::class);

    expect($family->students)->toHaveCount(0);
    expect(Enrollment::count())->toBe(0);
});

it('does not create due items or activate the student at registration', function () {
    $family = Family::factory()->create();
    $grade = Grade::factory()->create();
    Section::factory()->for($grade)->create();

    $student = app(RegisterStudent::class)->handle($family, studentPayload());

    expect(StudentDueItem::count())->toBe(0);
    expect($student->status)->toBe(Student::STATUS_PENDING_REGISTRATION);
});

it('audits student_registered', function () {
    $actor = User::factory()->create();
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();

    $student = app(RegisterStudent::class)->handle(
        $family,
        studentPayload(),
        [$guardian->id],
        null,
        $actor,
    );

    $log = AuditLog::query()->where('action', AuditLog::ACTION_STUDENT_REGISTERED)->sole();

    expect($log->actor->is($actor))->toBeTrue();
    expect($log->auditable->is($student))->toBeTrue();
    expect($log->metadata['student_id'])->toBe($student->id);
    expect($log->metadata['family_id'])->toBe($family->id);
    expect($log->metadata['linked_guardian_ids'])->toBe([$guardian->id]);
    expect($log->metadata['enrollment_created'])->toBeFalse();
});

it('applies a discount for one student and fee category and audits it', function () {
    $actor = User::factory()->create();
    $student = Student::factory()->create();
    $feeCategory = FeeCategory::factory()->create();
    $otherCategory = FeeCategory::factory()->create();

    $discount = app(ApplyStudentDiscount::class)->handle($student, $feeCategory, [
        'type' => 'scholarship',
        'value' => 25,
        'value_type' => 'amount',
    ], $actor);

    $log = AuditLog::query()->where('action', AuditLog::ACTION_DISCOUNT_APPLIED)->sole();

    expect($discount->student->is($student))->toBeTrue();
    expect($discount->feeCategory->is($feeCategory))->toBeTrue();
    expect($discount->feeCategory)->not->toBe($otherCategory);
    expect($discount->value)->toBe('25.00');
    expect($log->actor->is($actor))->toBeTrue();
    expect($log->auditable->is($discount))->toBeTrue();
    expect($log->metadata['fee_category_id'])->toBe($feeCategory->id);
});

it('does not modify existing due items when a discount is applied', function () {
    $student = Student::factory()->create();
    $feeCategory = FeeCategory::factory()->create();
    $dueItem = StudentDueItem::factory()
        ->for($student)
        ->for(AcademicYear::factory())
        ->for($feeCategory)
        ->create([
            'original_amount' => 100,
            'discount_amount' => 0,
            'net_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
        ]);
    $before = $dueItem->only(['original_amount', 'discount_amount', 'net_amount', 'balance_amount']);

    app(ApplyStudentDiscount::class)->handle($student, $feeCategory, [
        'type' => 'scholarship',
        'value' => 25,
        'value_type' => 'amount',
    ]);

    expect($dueItem->refresh()->only(array_keys($before)))->toBe($before);
    expect(Discount::count())->toBe(1);
});

it('creates an academic year scoped fee structure without touching due items', function () {
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => true]);
    $grade = Grade::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);
    $student = Student::factory()->create();
    $dueItem = StudentDueItem::factory()
        ->for($student)
        ->for($academicYear)
        ->for($feeCategory)
        ->create(['original_amount' => 100, 'net_amount' => 100, 'balance_amount' => 100]);
    $before = $dueItem->only(['original_amount', 'net_amount', 'balance_amount']);

    $feeStructure = app(CreateFeeStructure::class)->handle(
        $feeCategory,
        $grade,
        $academicYear,
        '150.00',
        'monthly',
    );

    expect($feeStructure->feeCategory->is($feeCategory))->toBeTrue();
    expect($feeStructure->grade->is($grade))->toBeTrue();
    expect($feeStructure->academicYear->is($academicYear))->toBeTrue();
    expect($feeStructure->amount)->toBe('150.00');
    expect($feeStructure->frequency)->toBe('monthly');
    expect($dueItem->refresh()->only(array_keys($before)))->toBe($before);
    expect(StudentDueItem::count())->toBe(1);
});

it('rejects a duplicate fee structure for the same category grade year and frequency', function () {
    $feeCategory = FeeCategory::factory()->create();
    $grade = Grade::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);
    app(CreateFeeStructure::class)->handle($feeCategory, $grade, $academicYear, '100.00', 'monthly');

    expect(fn () => app(CreateFeeStructure::class)->handle($feeCategory, $grade, $academicYear, '120.00', 'monthly'))
        ->toThrow(QueryException::class);
});

it('defers student activation because registration dues are not identifiable', function () {
    expect(class_exists(ActivateStudentAfterRegistrationPaid::class))->toBeFalse();
    expect((new FeeCategory)->getFillable())->not->toContain('is_registration_fee');
});
