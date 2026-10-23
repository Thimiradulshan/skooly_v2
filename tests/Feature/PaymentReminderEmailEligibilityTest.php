<?php

use App\Actions\Guardians\GrantGuardianPaymentReminderEmailConsent;
use App\Actions\Guardians\RevokeGuardianPaymentReminderEmailConsent;
use App\Actions\Notifications\PaymentReminderEmailDeliveryEligibility;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\Guardian;
use App\Models\PaymentReminder;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/**
 * @return array{academicYear: AcademicYear, family: Family, guardian: Guardian, student: Student, dueItem: StudentDueItem, reminder: PaymentReminder}
 */
function paymentReminderEmailEligibilityFixture(?AcademicYear $academicYear = null): array
{
    $academicYear ??= AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);

    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create(['email' => 'guardian@example.test']);
    $student = Student::factory()->for($family)->create();
    $guardian->students()->attach($student);
    $dueItem = StudentDueItem::factory()
        ->for($student)
        ->for($academicYear)
        ->for(FeeCategory::factory())
        ->create([
            'net_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => StudentDueItem::STATUS_UNPAID,
        ]);
    $reminder = PaymentReminder::factory()
        ->for($family)
        ->for($guardian)
        ->create(['due_item_ids' => [$dueItem->id]]);

    return compact('academicYear', 'family', 'guardian', 'student', 'dueItem', 'reminder');
}

function verifyGuardianPaymentReminderEmail(Guardian $guardian): Guardian
{
    $guardian->forceFill([
        'payment_reminder_email_opt_in_at' => now()->subMinute(),
        'payment_reminder_email_verified_at' => now(),
        'payment_reminder_email_opt_out_at' => null,
    ])->save();

    return $guardian->refresh();
}

it('records administrator-confirmed consent but requires separate email verification for delivery', function () {
    ['guardian' => $guardian, 'reminder' => $reminder] = paymentReminderEmailEligibilityFixture();
    $administrator = adminUser();

    $grantedGuardian = (new GrantGuardianPaymentReminderEmailConsent)->handle(
        $guardian,
        $administrator,
        'Written authorization received from guardian.',
    );

    expect($grantedGuardian->payment_reminder_email_opt_in_at)->not->toBeNull();
    expect($grantedGuardian->payment_reminder_email_verified_at)->toBeNull();
    expect((new PaymentReminderEmailDeliveryEligibility)->handle($reminder))->toBeFalse();
    $this->assertDatabaseHas('audit_logs', [
        'action' => AuditLog::ACTION_GUARDIAN_PAYMENT_REMINDER_EMAIL_CONSENT_GRANTED,
        'actor_user_id' => $administrator->id,
        'auditable_id' => $guardian->id,
    ]);
    expect(AuditLog::query()->where('action', AuditLog::ACTION_GUARDIAN_PAYMENT_REMINDER_EMAIL_CONSENT_GRANTED)->sole()->metadata['guardian_authorization_record'])
        ->toBe('Written authorization received from guardian.');

    verifyGuardianPaymentReminderEmail($guardian);

    expect((new PaymentReminderEmailDeliveryEligibility)->handle($reminder))->toBeTrue();
});

it('allows only administrators to record consent with an authorization record', function () {
    ['guardian' => $guardian] = paymentReminderEmailEligibilityFixture();

    expect(fn () => (new GrantGuardianPaymentReminderEmailConsent)->handle(
        $guardian,
        userWithRole(Role::ACCOUNTANT),
        'Written authorization received from guardian.',
    ))->toThrow(AuthorizationException::class);

    expect(fn () => (new GrantGuardianPaymentReminderEmailConsent)->handle(
        $guardian,
        adminUser(),
        ' ',
    ))->toThrow(InvalidArgumentException::class);
});

it('revokes consent and prevents further email delivery eligibility', function () {
    ['guardian' => $guardian, 'reminder' => $reminder] = paymentReminderEmailEligibilityFixture();
    $administrator = adminUser();
    verifyGuardianPaymentReminderEmail($guardian);

    (new RevokeGuardianPaymentReminderEmailConsent)->handle($guardian, $administrator);

    expect($guardian->refresh()->payment_reminder_email_opt_out_at)->not->toBeNull();
    expect((new PaymentReminderEmailDeliveryEligibility)->handle($reminder))->toBeFalse();
    $this->assertDatabaseHas('audit_logs', [
        'action' => AuditLog::ACTION_GUARDIAN_PAYMENT_REMINDER_EMAIL_CONSENT_REVOKED,
        'actor_user_id' => $administrator->id,
        'auditable_id' => $guardian->id,
    ]);
});

it('allows only administrators to revoke guardian email consent', function () {
    ['guardian' => $guardian] = paymentReminderEmailEligibilityFixture();
    verifyGuardianPaymentReminderEmail($guardian);

    expect(fn () => (new RevokeGuardianPaymentReminderEmailConsent)->handle(
        $guardian,
        userWithRole(Role::ACCOUNTANT),
    ))->toThrow(AuthorizationException::class);

    expect($guardian->refresh()->payment_reminder_email_opt_out_at)->toBeNull();
});

it('clears consent and verification when a guardian email changes', function () {
    ['guardian' => $guardian, 'reminder' => $reminder] = paymentReminderEmailEligibilityFixture();
    verifyGuardianPaymentReminderEmail($guardian);

    $guardian->update(['email' => 'new-guardian@example.test']);

    $guardian->refresh();

    expect($guardian->payment_reminder_email_opt_in_at)->toBeNull();
    expect($guardian->payment_reminder_email_verified_at)->toBeNull();
    expect($guardian->payment_reminder_email_opt_out_at)->toBeNull();
    expect((new PaymentReminderEmailDeliveryEligibility)->handle($reminder))->toBeFalse();
});

it('rejects a reminder for a guardian not explicitly linked to its student', function () {
    ['guardian' => $guardian, 'student' => $student, 'reminder' => $reminder] = paymentReminderEmailEligibilityFixture();
    $guardian->students()->detach($student);
    verifyGuardianPaymentReminderEmail($guardian);

    expect((new PaymentReminderEmailDeliveryEligibility)->handle($reminder))->toBeFalse();
});

it('rejects cross-family reminder access even if a malformed link exists', function () {
    $currentAcademicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($currentAcademicYear);
    $guardianFamily = Family::factory()->create();
    $studentFamily = Family::factory()->create();
    $guardian = Guardian::factory()->for($guardianFamily)->create(['email' => 'guardian@example.test']);
    $student = Student::factory()->for($studentFamily)->create();
    $guardian->students()->attach($student);
    $dueItem = StudentDueItem::factory()
        ->for($student)
        ->for($currentAcademicYear)
        ->for(FeeCategory::factory())
        ->create();
    $reminder = PaymentReminder::factory()
        ->for($guardianFamily)
        ->for($guardian)
        ->create(['due_item_ids' => [$dueItem->id]]);
    verifyGuardianPaymentReminderEmail($guardian);

    expect((new PaymentReminderEmailDeliveryEligibility)->handle($reminder))->toBeFalse();
});

it('rejects reminders for settled balances', function () {
    ['guardian' => $guardian, 'dueItem' => $dueItem, 'reminder' => $reminder] = paymentReminderEmailEligibilityFixture();
    verifyGuardianPaymentReminderEmail($guardian);
    $dueItem->update([
        'paid_amount' => 100,
        'balance_amount' => 0,
        'status' => StudentDueItem::STATUS_PAID,
    ]);

    expect((new PaymentReminderEmailDeliveryEligibility)->handle($reminder))->toBeFalse();
});

it('rejects reminders for due items in archived academic years', function () {
    $archivedAcademicYear = AcademicYear::factory()->create(['is_archived' => true]);
    ['guardian' => $guardian, 'reminder' => $reminder] = paymentReminderEmailEligibilityFixture($archivedAcademicYear);
    setActiveAcademicYear(AcademicYear::factory()->create());
    verifyGuardianPaymentReminderEmail($guardian);

    expect((new PaymentReminderEmailDeliveryEligibility)->handle($reminder))->toBeFalse();
});

it('rejects reminders for due items outside the current academic year', function () {
    $historicalAcademicYear = AcademicYear::factory()->create();
    ['guardian' => $guardian, 'reminder' => $reminder] = paymentReminderEmailEligibilityFixture($historicalAcademicYear);
    setActiveAcademicYear(AcademicYear::factory()->create());
    verifyGuardianPaymentReminderEmail($guardian);

    expect((new PaymentReminderEmailDeliveryEligibility)->handle($reminder))->toBeFalse();
});

it('rejects a combined reminder when any due item belongs to an unlinked student', function () {
    ['academicYear' => $academicYear, 'family' => $family, 'guardian' => $guardian, 'reminder' => $reminder] = paymentReminderEmailEligibilityFixture();
    $unlinkedStudent = Student::factory()->for($family)->create();
    $unlinkedDueItem = StudentDueItem::factory()
        ->for($unlinkedStudent)
        ->for($academicYear)
        ->for(FeeCategory::factory())
        ->create();
    $reminder->update(['due_item_ids' => [$reminder->due_item_ids[0], $unlinkedDueItem->id]]);
    verifyGuardianPaymentReminderEmail($guardian);

    expect((new PaymentReminderEmailDeliveryEligibility)->handle($reminder))->toBeFalse();
});

it('rejects cancelled reminders', function () {
    ['guardian' => $guardian, 'reminder' => $reminder] = paymentReminderEmailEligibilityFixture();
    verifyGuardianPaymentReminderEmail($guardian);
    $reminder->update(['status' => PaymentReminder::STATUS_CANCELLED]);

    expect((new PaymentReminderEmailDeliveryEligibility)->handle($reminder))->toBeFalse();
});
