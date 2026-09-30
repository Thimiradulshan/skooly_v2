<?php

use App\Models\AcademicYear;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentReminder;
use App\Models\Receipt;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

function webReminderStudent(Family $family): Student
{
    return Student::factory()->for($family)->create();
}

function webReminderDueItem(Student $student, array $attributes = []): StudentDueItem
{
    return StudentDueItem::factory()
        ->for($student)
        ->for(AcademicYear::factory())
        ->for(FeeCategory::factory())
        ->create(array_merge([
            'due_date' => '2026-10-10',
            'net_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => StudentDueItem::STATUS_UNPAID,
            'description' => 'Reminder due item',
        ], $attributes));
}

function reminderGenerationPayload(array $overrides = []): array
{
    return array_merge([
        'as_of_date' => '2026-10-05',
        'upcoming_window_days' => 7,
    ], $overrides);
}

it('denies guests the payment reminders index', function () {
    $this->get(route('payment-reminders.index'))->assertRedirect(route('login'));
});

it('denies teachers and accountants the payment reminders index', function () {
    $this->actingAs(userWithRole(Role::TEACHER))->get(route('payment-reminders.index'))->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->get(route('payment-reminders.index'))->assertForbidden();
});

it('lets an admin access the payment reminder index and generation form', function () {
    $this->actingAs(adminUser())->get(route('payment-reminders.index'))->assertOk();
    $this->actingAs(adminUser())->get(route('payment-reminders.create'))->assertOk();
});

it('generates overdue reminder records through the web route', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $student = webReminderStudent($family);
    $guardian = Guardian::factory()->for($family)->create();
    $guardian->students()->attach($student);
    $dueItem = webReminderDueItem($student, ['due_date' => '2026-09-01']);

    $this->actingAs(adminUser())
        ->post(route('payment-reminders.store'), reminderGenerationPayload())
        ->assertRedirect(route('payment-reminders.index'))
        ->assertSessionHas('status');

    $reminder = PaymentReminder::query()->sole();

    expect($reminder->reminder_type)->toBe(PaymentReminder::TYPE_OVERDUE);
    expect($reminder->guardian_id)->toBe($guardian->id);
    expect($reminder->student_due_item_id)->toBe($dueItem->id);
    expect($reminder->status)->toBe(PaymentReminder::STATUS_PENDING);
});

it('generates upcoming reminder records through the web route', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $student = webReminderStudent($family);
    $guardian = Guardian::factory()->for($family)->create();
    $guardian->students()->attach($student);
    webReminderDueItem($student, ['due_date' => '2026-10-10']);

    $this->actingAs(adminUser())
        ->post(route('payment-reminders.store'), reminderGenerationPayload())
        ->assertRedirect(route('payment-reminders.index'));

    expect(PaymentReminder::query()->sole()->reminder_type)->toBe(PaymentReminder::TYPE_UPCOMING);
});

it('generates reminders only for explicitly linked guardians', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $student = webReminderStudent($family);
    $linkedGuardian = Guardian::factory()->for($family)->create();
    $linkedGuardian->students()->attach($student);
    $unrelatedGuardian = Guardian::factory()->for($family)->create();
    $unrelatedGuardian->students()->attach(webReminderStudent($family));
    webReminderDueItem($student);

    $this->actingAs(adminUser())->post(route('payment-reminders.store'), reminderGenerationPayload());

    expect(PaymentReminder::query()->sole()->guardian_id)->toBe($linkedGuardian->id);
    expect(PaymentReminder::where('guardian_id', $unrelatedGuardian->id)->count())->toBe(0);
});

it('creates a consolidated reminder for combined billing', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => true]);
    $guardian = Guardian::factory()->for($family)->create();
    $firstStudent = webReminderStudent($family);
    $secondStudent = webReminderStudent($family);
    $guardian->students()->attach([$firstStudent->id, $secondStudent->id]);
    $firstDueItem = webReminderDueItem($firstStudent, ['due_date' => '2026-10-06']);
    $secondDueItem = webReminderDueItem($secondStudent, ['due_date' => '2026-10-07']);

    $this->actingAs(adminUser())->post(route('payment-reminders.store'), reminderGenerationPayload());

    $reminder = PaymentReminder::query()->sole();

    expect($reminder->student_due_item_id)->toBeNull();
    expect($reminder->due_item_ids)->toHaveCount(2);
    expect($reminder->due_item_ids)->toContain($firstDueItem->id, $secondDueItem->id);
});

it('creates separate due-item reminders when combined billing is off', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $guardian = Guardian::factory()->for($family)->create();
    $firstStudent = webReminderStudent($family);
    $secondStudent = webReminderStudent($family);
    $guardian->students()->attach([$firstStudent->id, $secondStudent->id]);
    $firstDueItem = webReminderDueItem($firstStudent, ['due_date' => '2026-10-06']);
    $secondDueItem = webReminderDueItem($secondStudent, ['due_date' => '2026-10-07']);

    $this->actingAs(adminUser())->post(route('payment-reminders.store'), reminderGenerationPayload());

    expect(PaymentReminder::count())->toBe(2);
    expect(PaymentReminder::query()->pluck('student_due_item_id')->all())
        ->toEqualCanonicalizing([$firstDueItem->id, $secondDueItem->id]);
});

it('does not create payment records or modify due items when generating reminders', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $student = webReminderStudent($family);
    $guardian = Guardian::factory()->for($family)->create();
    $guardian->students()->attach($student);
    $dueItem = webReminderDueItem($student, [
        'paid_amount' => 20,
        'balance_amount' => 80,
        'status' => StudentDueItem::STATUS_PARTIALLY_PAID,
    ]);
    $before = $dueItem->only(['paid_amount', 'balance_amount', 'status']);

    $this->actingAs(adminUser())->post(route('payment-reminders.store'), reminderGenerationPayload());

    expect(Payment::count())->toBe(0);
    expect(Receipt::count())->toBe(0);
    expect(PaymentAllocation::count())->toBe(0);
    expect($dueItem->refresh()->only(array_keys($before)))->toBe($before);
});

it('displays stored reminder snapshot data on the detail page', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => true, 'family_code' => 'FAM-PREVIEW']);
    $guardian = Guardian::factory()->for($family)->create(['name' => 'Preview Guardian']);
    $student = webReminderStudent($family);
    $guardian->students()->attach($student);
    webReminderDueItem($student, [
        'description' => 'Original reminder due',
        'due_date' => '2026-10-10',
        'balance_amount' => 80,
    ]);

    $this->actingAs(adminUser())->post(route('payment-reminders.store'), reminderGenerationPayload());
    $reminder = PaymentReminder::query()->sole();

    $this->actingAs(adminUser())
        ->get(route('payment-reminders.show', $reminder))
        ->assertOk()
        ->assertSee('FAM-PREVIEW')
        ->assertSee('Preview Guardian')
        ->assertSee('Original reminder due')
        ->assertSee('80.00');
});

it('does not duplicate reminder records when generation runs twice', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $student = webReminderStudent($family);
    $guardian = Guardian::factory()->for($family)->create();
    $guardian->students()->attach($student);
    webReminderDueItem($student);

    $this->actingAs(adminUser())->post(route('payment-reminders.store'), reminderGenerationPayload());
    $this->actingAs(adminUser())->post(route('payment-reminders.store'), reminderGenerationPayload());

    expect(PaymentReminder::count())->toBe(1);
});

it('adds no send edit or delete reminder route', function () {
    expect(Route::has('payment-reminders.send'))->toBeFalse();
    expect(Route::has('payment-reminders.edit'))->toBeFalse();
    expect(Route::has('payment-reminders.update'))->toBeFalse();
    expect(Route::has('payment-reminders.destroy'))->toBeFalse();

    $reminder = PaymentReminder::factory()->create();

    $this->actingAs(adminUser())
        ->delete(route('payment-reminders.show', $reminder))
        ->assertMethodNotAllowed();
});
