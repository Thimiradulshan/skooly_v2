<?php

use App\Actions\Privacy\AuthorizeGuardianStudentAccess;
use App\Actions\Privacy\ListGuardianVisibleDueItems;
use App\Actions\Privacy\ListGuardianVisibleStudents;
use App\Models\AcademicYear;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentReminder;
use App\Models\PromotionBatch;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentDueItem;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(LazilyRefreshDatabase::class);

function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user->refresh();
}

function privacyDueItem(Student $student, array $attributes = []): StudentDueItem
{
    return StudentDueItem::factory()
        ->for($student)
        ->for(AcademicYear::factory())
        ->for(FeeCategory::factory())
        ->create(array_merge([
            'net_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => StudentDueItem::STATUS_UNPAID,
            'due_date' => '2026-10-10',
        ], $attributes));
}

it('resolves user roles with hasRole and hasAnyRole', function () {
    $user = userWithRole(Role::ADMIN);

    expect($user->hasRole(Role::ADMIN))->toBeTrue();
    expect($user->hasRole(Role::TEACHER))->toBeFalse();
    expect($user->hasAnyRole([Role::ADMIN, Role::ACCOUNTANT]))->toBeTrue();
    expect($user->hasAnyRole([Role::ACCOUNTANT, Role::TEACHER]))->toBeFalse();
});

it('lets an admin view and manage student records', function () {
    $admin = userWithRole(Role::ADMIN);
    $student = Student::factory()->create();

    expect(Gate::forUser($admin)->allows('viewAny', Student::class))->toBeTrue();
    expect(Gate::forUser($admin)->allows('view', $student))->toBeTrue();
    expect(Gate::forUser($admin)->allows('create', Student::class))->toBeTrue();
    expect(Gate::forUser($admin)->allows('update', $student))->toBeTrue();
    expect(Gate::forUser($admin)->allows('delete', $student))->toBeTrue();
});

it('denies student records to a teacher and an accountant', function () {
    $teacher = User::factory()->teacher()->create();
    $accountant = userWithRole(Role::ACCOUNTANT);
    $student = Student::factory()->create();

    expect(Gate::forUser($teacher)->allows('view', $student))->toBeFalse();
    expect(Gate::forUser($accountant)->allows('view', $student))->toBeFalse();
});

it('lets an accountant view financial records', function () {
    $accountant = userWithRole(Role::ACCOUNTANT);
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = privacyDueItem($student);
    $payment = Payment::factory()->for($family)->create();
    $reminder = PaymentReminder::factory()->for($family)->create();

    expect(Gate::forUser($accountant)->allows('viewAny', StudentDueItem::class))->toBeTrue();
    expect(Gate::forUser($accountant)->allows('view', $dueItem))->toBeTrue();
    expect(Gate::forUser($accountant)->allows('view', $payment))->toBeTrue();
    expect(Gate::forUser($accountant)->allows('view', $reminder))->toBeTrue();
});

it('denies financial details to a teacher', function () {
    $teacher = User::factory()->teacher()->create();
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = privacyDueItem($student);
    $payment = Payment::factory()->for($family)->create();
    $reminder = PaymentReminder::factory()->for($family)->create();

    expect(Gate::forUser($teacher)->allows('viewAny', StudentDueItem::class))->toBeFalse();
    expect(Gate::forUser($teacher)->allows('view', $dueItem))->toBeFalse();
    expect(Gate::forUser($teacher)->allows('view', $payment))->toBeFalse();
    expect(Gate::forUser($teacher)->allows('view', $reminder))->toBeFalse();
    expect(Gate::forUser($teacher)->allows('viewAny', Student::class))->toBeFalse();
});

it('restricts promotion batches to admins', function () {
    $admin = userWithRole(Role::ADMIN);
    $accountant = userWithRole(Role::ACCOUNTANT);
    $batch = PromotionBatch::factory()->create();

    expect(Gate::forUser($admin)->allows('view', $batch))->toBeTrue();
    expect(Gate::forUser($accountant)->allows('view', $batch))->toBeFalse();
});

it('authorizes a guardian who is explicitly linked to a student', function () {
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();
    $student = Student::factory()->for($family)->create();
    $guardian->students()->attach($student);

    expect(app(AuthorizeGuardianStudentAccess::class)->handle($guardian, $student))->toBeTrue();
});

it('denies a guardian who is not linked to the student', function () {
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();
    $student = Student::factory()->create();

    expect(app(AuthorizeGuardianStudentAccess::class)->handle($guardian, $student))->toBeFalse();
});

it('denies a guardian in the same family who is not linked to that student', function () {
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();
    $student = Student::factory()->for($family)->create();

    expect($guardian->family->is($student->family))->toBeTrue();
    expect(app(AuthorizeGuardianStudentAccess::class)->handle($guardian, $student))->toBeFalse();
});

it('lists only explicitly linked students for a guardian', function () {
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();
    $linkedStudent = Student::factory()->for($family)->create();
    $unlinkedStudent = Student::factory()->for($family)->create();
    $guardian->students()->attach($linkedStudent);

    $visible = app(ListGuardianVisibleStudents::class)->handle($guardian);

    expect($visible)->toHaveCount(1);
    expect($visible->sole()->is($linkedStudent))->toBeTrue();
    expect($visible->pluck('id')->all())->not->toContain($unlinkedStudent->id);
});

it('lists due items only for explicitly linked students', function () {
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();
    $linkedStudent = Student::factory()->for($family)->create();
    $unlinkedStudent = Student::factory()->for($family)->create();
    $guardian->students()->attach($linkedStudent);
    $linkedDueItem = privacyDueItem($linkedStudent);
    $unlinkedDueItem = privacyDueItem($unlinkedStudent);

    $visible = app(ListGuardianVisibleDueItems::class)->handle($guardian);

    expect($visible)->toHaveCount(1);
    expect($visible->sole()->is($linkedDueItem))->toBeTrue();
    expect($visible->pluck('id')->all())->not->toContain($unlinkedDueItem->id);
});

it('does not expose sibling due items to an unlinked guardian when combined billing is on', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => true]);
    $guardian = Guardian::factory()->for($family)->create();
    $linkedStudent = Student::factory()->for($family)->create();
    $sibling = Student::factory()->for($family)->create();
    $guardian->students()->attach($linkedStudent);
    $linkedDueItem = privacyDueItem($linkedStudent);
    $siblingDueItem = privacyDueItem($sibling);

    $visible = app(ListGuardianVisibleDueItems::class)->handle($guardian);

    expect($family->combined_billing_enabled)->toBeTrue();
    expect($visible->pluck('id')->all())->toBe([$linkedDueItem->id]);
    expect($visible->pluck('id')->all())->not->toContain($siblingDueItem->id);
    expect(app(AuthorizeGuardianStudentAccess::class)->handle($guardian, $sibling))->toBeFalse();
});

it('does not leak sibling payment and receipt details through due item visibility', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => true]);
    $guardian = Guardian::factory()->for($family)->create();
    $linkedStudent = Student::factory()->for($family)->create();
    $sibling = Student::factory()->for($family)->create();
    $guardian->students()->attach($linkedStudent);
    $linkedDueItem = privacyDueItem($linkedStudent);
    $siblingDueItem = privacyDueItem($sibling);
    $siblingPayment = Payment::factory()->for($family)->create();

    $visible = app(ListGuardianVisibleDueItems::class)->handle($guardian);
    $visibleAllocations = StudentDueItem::query()
        ->whereIn('id', $visible->pluck('id'))
        ->with('paymentAllocations')
        ->get()
        ->flatMap(fn (StudentDueItem $dueItem) => $dueItem->paymentAllocations);

    expect($visible->pluck('id')->all())->toBe([$linkedDueItem->id]);
    expect($visible->pluck('id')->all())->not->toContain($siblingDueItem->id);
    expect($visibleAllocations->pluck('payment_id')->all())->not->toContain($siblingPayment->id);
    expect(StudentDueItem::query()->where('id', $siblingDueItem->id)->exists())->toBeTrue();
});

it('scopes payment reminder visibility to guardian and due item snapshot', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => true]);
    $guardian = Guardian::factory()->for($family)->create();
    $linkedStudent = Student::factory()->for($family)->create();
    $sibling = Student::factory()->for($family)->create();
    $guardian->students()->attach($linkedStudent);
    $linkedDueItem = privacyDueItem($linkedStudent);
    $siblingDueItem = privacyDueItem($sibling);
    $reminder = PaymentReminder::factory()->for($family)->for($guardian)->create([
        'due_item_ids' => [$linkedDueItem->id],
    ]);
    PaymentReminder::factory()->for($family)->for($guardian)->create([
        'due_item_ids' => [$siblingDueItem->id],
    ]);

    $visibleIds = PaymentReminder::query()
        ->where('guardian_id', $guardian->id)
        ->pluck('due_item_ids')
        ->flatten()
        ->all();

    expect(PaymentReminder::query()->where('guardian_id', $guardian->id)->count())->toBe(2);
    expect($reminder->due_item_ids)->toBe([$linkedDueItem->id]);
    expect($visibleIds)->toContain($linkedDueItem->id, $siblingDueItem->id);
    expect($reminder->guardian_id)->toBe($guardian->id);
});
