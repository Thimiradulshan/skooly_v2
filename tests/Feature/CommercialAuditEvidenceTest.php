<?php

use App\Actions\Fees\GenerateRecurringDueItems;
use App\Actions\Notifications\QueuePaymentReminderDeliveryAttempt;
use App\Actions\Promotion\ConfirmPromotionBatch;
use App\Actions\Promotion\CreatePromotionBatch;
use App\Actions\Promotion\UpdatePromotionBatchItem;
use App\Jobs\DeliverPaymentReminderEmail;
use App\Mail\PaymentReminderEmail;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentReminder;
use App\Models\PaymentReminderDeliveryAttempt;
use App\Models\PromotionBatchItem;
use App\Models\Role;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDueItem;
use App\Models\StudentFeeSubscription;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\Term;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class)->group('audit-evidence');

/**
 * Characterization evidence, not desired security/business contracts.
 * These assertions document open CA findings; invert them when remediating.
 */
it('CA-01 reproduces an inactive admin retaining mutation access through an existing session', function () {
    $admin = adminUser();
    $this->actingAs($admin);
    $admin->update(['is_active' => false]);

    $this->post(route('grades.store'), ['name' => 'Audit inactive session grade', 'sequence_order' => 77])
        ->assertRedirect();

    $this->assertDatabaseHas('grades', ['name' => 'Audit inactive session grade']);
});

it('CA-02 reproduces receipt output without either child identity for a combined payment', function () {
    $family = Family::factory()->create();
    $first = Student::factory()->for($family)->create(['name' => 'Audit First Child']);
    $second = Student::factory()->for($family)->create(['name' => 'Audit Second Child']);
    $firstDue = StudentDueItem::factory()->for($first)->create(['description' => 'Tuition']);
    $secondDue = StudentDueItem::factory()->for($second)->create(['description' => 'Tuition']);
    $payment = Payment::recordManual($family, 'AUDIT-COMBINED', 'cash', '20.00', [
        ['student_due_item_id' => $firstDue->id, 'amount' => '10.00'],
        ['student_due_item_id' => $secondDue->id, 'amount' => '10.00'],
    ]);

    $this->actingAs(adminUser())->get(route('receipts.show', $payment->receipt))
        ->assertOk()->assertSee('Tuition')
        ->assertDontSee('Audit First Child')->assertDontSee('Audit Second Child');
    expect($payment->receipt->allocation_snapshot[0])->not->toHaveKey('student_id');
});

it('CA-03 reproduces a pending registration student appearing in the teacher roster', function () {
    $year = AcademicYear::factory()->create();
    setActiveAcademicYear($year);
    $teacher = userWithRole(Role::TEACHER);
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $student = Student::factory()->create(['name' => 'Audit Pending Learner', 'status' => Student::STATUS_PENDING_REGISTRATION]);
    Enrollment::factory()->for($student)->for($year)->for($grade)->for($section)->create();
    TeacherAssignment::factory()->create([
        'teacher_id' => $teacher->id, 'academic_year_id' => $year->id,
        'section_id' => $section->id, 'subject_id' => Subject::factory()->create()->id,
    ]);

    $this->actingAs($teacher)->get(route('teacher.students'))->assertOk()->assertSee('Audit Pending Learner');
});

it('CA-04 reproduces immediate reminder delivery instead of pushing a job to the queue', function () {
    Mail::fake();
    Queue::fake();
    config(['mail.default' => 'array', 'queue.default' => 'database']);
    $year = AcademicYear::factory()->create();
    setActiveAcademicYear($year);
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create(['email' => 'audit@example.test']);
    $guardian->forceFill(['payment_reminder_email_opt_in_at' => now(), 'payment_reminder_email_verified_at' => now()])->save();
    $student = Student::factory()->for($family)->create();
    $guardian->students()->attach($student);
    $due = StudentDueItem::factory()->for($student)->for($year)->create(['due_date' => '2026-12-01']);
    $reminder = PaymentReminder::factory()->for($family)->for($guardian)->create(['due_item_ids' => [$due->id]]);
    $attempt = (new QueuePaymentReminderDeliveryAttempt)->handle($reminder);

    Bus::dispatch(new DeliverPaymentReminderEmail($attempt->id));

    Queue::assertNothingPushed();
    Mail::assertSent(PaymentReminderEmail::class);
    expect($attempt->refresh()->status)->toBe(PaymentReminderDeliveryAttempt::STATUS_TRANSPORT_ACCEPTED);
});

it('CA-05 reproduces a web family creation audit without the authenticated actor', function () {
    $this->actingAs(adminUser())->post(route('families.store'), [
        'family_code' => 'AUDIT-ACTOR', 'combined_billing_enabled' => true,
    ])->assertRedirect();

    expect(AuditLog::query()->where('action', AuditLog::ACTION_FAMILY_CREATED)->sole()->actor_user_id)->toBeNull();
});

it('CA-06 reproduces retaining a student into a different grade', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    setActiveAcademicYear($target);
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $next = Grade::factory()->create(['sequence_order' => 2]);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    $nextSection = Section::factory()->for($next)->create(['name' => 'A']);
    $student = Student::factory()->create(['status' => Student::STATUS_ACTIVE]);
    Enrollment::factory()->for($student)->for($source, 'academicYear')->for($grade)->for($section)->create();
    $batch = (new CreatePromotionBatch)->handle($source, $target, [$section->id]);
    (new UpdatePromotionBatchItem)->handle($batch, $batch->items->sole(), PromotionBatchItem::ACTION_RETAIN, $next->id, $nextSection->id);

    (new ConfirmPromotionBatch)->handle($batch);

    $this->assertDatabaseHas('enrollments', ['student_id' => $student->id, 'academic_year_id' => $target->id, 'grade_id' => $next->id]);
});

it('CA-07 reproduces charging before an opt-in subscription starts', function () {
    $year = AcademicYear::factory()->create();
    setActiveAcademicYear($year);
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $student = Student::factory()->create(['status' => Student::STATUS_ACTIVE]);
    Enrollment::factory()->for($student)->for($year)->for($grade)->for($section)->create();
    $category = FeeCategory::factory()->create(['is_recurring' => true, 'is_opt_in' => true]);
    FeeStructure::factory()->for($year)->for($grade)->for($category)->create(['amount' => '100.00', 'frequency' => 'monthly']);
    StudentFeeSubscription::factory()->for($student)->for($year)->for($category)->create(['is_active' => true, 'starts_on' => '2027-01-01']);

    expect((new GenerateRecurringDueItems)->handle($year, '2026-10-01', '2026-10'))->toBe(1);
    expect(StudentDueItem::query()->sole()->balance_amount)->toBe('100.00');
});

it('records isolated event and term request measurements without touching local records', function () {
    $year = AcademicYear::factory()->create();
    setActiveAcademicYear($year);
    $category = FeeCategory::factory()->create();
    $term = Term::factory()->for($year)->create();
    $this->actingAs(adminUser())->get(route('events.create'))->assertOk();
    $payload = ['academic_year_id' => $year->id, 'fee_category_id' => $category->id, 'name' => 'Audit timing event', 'event_date' => '2026-10-23'];

    foreach (['event create', 'event update', 'term archive', 'term restore'] as $operation) {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $start = hrtime(true);
        $response = match ($operation) {
            'event create' => $this->post(route('events.store'), $payload),
            'event update' => $this->put(route('events.update', Event::query()->sole()), array_merge($payload, ['name' => 'Updated timing event'])),
            'term archive' => $this->post(route('terms.archive', $term)),
            'term restore' => $this->post(route('terms.restore', $term)),
        };
        $duration = (hrtime(true) - $start) / 1e6;
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $response->assertRedirect()->assertSessionHas('status');
        fwrite(STDERR, sprintf("AUDIT timing %s: HTTP %d %.2f ms, %d queries, %.2f query ms\n", $operation, $response->status(), $duration, count($queries), array_sum(array_column($queries, 'time'))));
    }

    expect(Event::query()->sole()->name)->toBe('Updated timing event');
    expect($term->refresh()->is_archived)->toBeFalse();
});

it('CA-14 provides an initial school settings setup path', function () {
    $academicYear = AcademicYear::factory()->create();

    $this->actingAs(adminUser())->get(route('school-settings.edit'))
        ->assertOk()
        ->assertViewIs('school-settings.edit')
        ->assertSee('Initial setup is incomplete');

    $this->actingAs(adminUser())->put(route('school-settings.update'), [
        'active_academic_year_id' => $academicYear->id,
    ])->assertRedirect(route('school-settings.edit'));

    $this->assertDatabaseHas('school_settings', [
        'id' => 1,
        'active_academic_year_id' => $academicYear->id,
    ]);
});

it('CA-18 reproduces a staff email change keeping the old verification timestamp', function () {
    $target = userWithRole(Role::TEACHER);
    $verifiedAt = $target->email_verified_at;

    $this->actingAs(adminUser())->put(route('users.update', $target), [
        'name' => $target->name, 'email' => 'changed-audit@example.test',
        'roles' => [Role::TEACHER], 'is_active' => true,
    ])->assertRedirect();

    expect($target->refresh()->email)->toBe('changed-audit@example.test');
    expect($target->email_verified_at->equalTo($verifiedAt))->toBeTrue();
});

it('CA-21 reproduces SQL diagnostics flashed to an admin after a promotion conflict', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    setActiveAcademicYear($target);
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $next = Grade::factory()->create(['sequence_order' => 2]);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    $nextSection = Section::factory()->for($next)->create(['name' => 'A']);
    $student = Student::factory()->create(['status' => Student::STATUS_ACTIVE]);
    Enrollment::factory()->for($student)->for($source, 'academicYear')->for($grade)->for($section)->create();
    $batch = (new CreatePromotionBatch)->handle($source, $target, [$section->id]);
    Enrollment::factory()->for($student)->for($target, 'academicYear')->for($next, 'grade')->for($nextSection, 'section')->create();

    $this->actingAs(adminUser())->post(route('promotion-batches.confirm', $batch))
        ->assertRedirect()->assertSessionHasErrors('promotion_batch');

    expect(session('errors')->first('promotion_batch'))->toContain('SQLSTATE');
});
