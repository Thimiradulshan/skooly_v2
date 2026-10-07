<?php

use App\Models\AcademicYear;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventCharge;
use App\Models\EventParticipation;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\Grade;
use App\Models\Payment;
use App\Models\PaymentReminder;
use App\Models\PromotionBatch;
use App\Models\Receipt;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDueItem;
use App\Models\StudentFeeSubscription;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    setActiveAcademicYear(AcademicYear::factory()->create());
});

function qaActiveStudent(AcademicYear $academicYear, Grade $grade, Section $section): Student
{
    $student = Student::factory()->for(Family::factory())->create(['status' => Student::STATUS_ACTIVE]);
    Enrollment::factory()->for($student)->for($academicYear)->for($grade)->for($section)->create();

    return $student;
}

it('loads every admin page for a logged in admin', function () {
    $admin = adminUser();
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();

    $pages = [
        route('admin.dashboard'),
        route('families.index'),
        route('families.create'),
        route('families.show', $family),
        route('families.edit', $family),
        route('families.students.create', $family),
        route('families.payments.create', $family),
        route('fee-categories.index'),
        route('fee-categories.create'),
        route('fee-structures.index'),
        route('fee-structures.create'),
        route('students.discounts.create', $student),
        route('students.fee-subscriptions.create', $student),
        route('due-generation.recurring.create'),
        route('due-generation.events.create'),
        route('dues-dashboard.index'),
        route('events.index'),
        route('events.create'),
        route('promotion-batches.index'),
        route('promotion-batches.create'),
        route('payment-reminders.index'),
        route('payment-reminders.create'),
    ];

    foreach ($pages as $page) {
        $this->actingAs($admin)->get($page)->assertOk();
    }
});

it('keeps destructive and api routes absent', function () {
    $dangerous = [
        'families.destroy',
        'fee-categories.destroy',
        'fee-structures.destroy',
        'events.destroy',
        'events.charges.destroy',
        'events.participation.destroy',
        'promotion-batches.destroy',
        'payment-reminders.destroy',
        'payment-reminders.send',
        'payments.update',
        'payments.destroy',
        'payments.refund',
        'receipts.destroy',
        'receipts.export',
    ];

    foreach ($dangerous as $name) {
        expect(Route::has($name))->toBeFalse();
    }

    $apiRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'api/'));

    expect($apiRoutes)->toBeEmpty();
});

it('renders the event show page when the event has charges participation and dues', function () {
    $admin = adminUser();
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2]);
    $section = Section::factory()->for($grade)->create();
    $student = qaActiveStudent($academicYear, $grade, $section);
    $event = Event::factory()->for($academicYear)->create(['is_mandatory' => false]);
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 30]);
    EventParticipation::factory()->for($event)->for($student)->create();

    $this->actingAs($admin)
        ->post(route('due-generation.events.store'), ['event_id' => $event->id])
        ->assertRedirect();

    expect($event->refresh()->eventDueItems()->count())->toBe(1);

    $this->actingAs($admin)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee($student->name)
        ->assertSee('30.00')
        ->assertSee(EventParticipation::STATUS_OPTED_IN);
});

it('renders the promotion batch show page after confirmation', function () {
    $admin = adminUser();
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    setActiveAcademicYear($target);
    $promoteGrade = Grade::factory()->create(['sequence_order' => 1]);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2]);
    Section::factory()->for($nextGrade)->create(['name' => 'A']);
    $promoteSection = Section::factory()->for($promoteGrade)->create(['name' => 'A']);
    $promoting = qaActiveStudent($source, $promoteGrade, $promoteSection);

    $topGrade = Grade::factory()->create(['sequence_order' => 99]);
    $topSection = Section::factory()->for($topGrade)->create();
    $graduating = qaActiveStudent($source, $topGrade, $topSection);

    $this->actingAs($admin)
        ->post(route('promotion-batches.store'), [
            'source_academic_year_id' => $source->id,
            'target_academic_year_id' => $target->id,
            'source_section_ids' => [$promoteSection->id, $topSection->id],
        ])
        ->assertRedirect();

    $batch = PromotionBatch::query()->sole();
    expect($batch->items()->count())->toBe(2);

    $this->actingAs($admin)
        ->post(route('promotion-batches.confirm', $batch))
        ->assertRedirect();

    $this->actingAs($admin)
        ->get(route('promotion-batches.show', $batch))
        ->assertOk()
        ->assertSee('confirmed')
        ->assertDontSee('Confirm promotion batch')
        ->assertSee($promoting->name)
        ->assertSee($graduating->name);

    expect($promoting->refresh()->status)->toBe(Student::STATUS_ACTIVE);
    expect($graduating->refresh()->status)->toBe(Student::STATUS_GRADUATED);
    expect(Enrollment::where('academic_year_id', $target->id)->count())->toBe(1);
    expect(StudentDueItem::count())->toBe(0);
});

it('does not crash on any dashboard filter combination', function () {
    $admin = adminUser();
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $family = Family::factory()->create();
    $feeCategory = FeeCategory::factory()->create();
    $student = Student::factory()->for($family)->create();
    StudentDueItem::factory()->for($student)->for($academicYear)->for($feeCategory)->create([
        'due_date' => '2026-10-10',
        'net_amount' => 100,
        'balance_amount' => 100,
    ]);

    $combinations = [
        ['academic_year_id' => $academicYear->id],
        ['grade_id' => $grade->id, 'academic_year_id' => $academicYear->id],
        ['section_id' => $section->id, 'academic_year_id' => $academicYear->id],
        ['fee_category_id' => $feeCategory->id],
        ['family_id' => $family->id],
        ['due_date_from' => '2026-01-01', 'due_date_to' => '2026-12-31'],
        ['due_date_from' => '2026-01-01', 'due_date_to' => '2026-12-31', 'family_id' => $family->id],
        [],
    ];

    foreach ($combinations as $query) {
        $this->actingAs($admin)->get(route('dues-dashboard.index', $query))->assertOk();
    }

    $this->actingAs($admin)
        ->get(route('dues-dashboard.index', ['grade_id' => $grade->id]))
        ->assertSessionHasErrors('grade_id');

    $this->actingAs($admin)
        ->get(route('dues-dashboard.index', ['section_id' => $section->id]))
        ->assertSessionHasErrors('section_id');

    $this->actingAs($admin)
        ->get(route('dues-dashboard.index', ['due_date_from' => '2026-12-31', 'due_date_to' => '2026-01-01']))
        ->assertSessionHasErrors('due_date_to');
});

it('updates family and event through their edit forms', function () {
    $admin = adminUser();
    $family = Family::factory()->create(['family_code' => 'FAM-EDIT', 'address' => 'Old']);
    $academicYear = AcademicYear::factory()->create();
    $feeCategory = FeeCategory::factory()->create();

    $this->actingAs($admin)->put(route('families.update', $family), [
        'family_code' => 'FAM-EDITED',
        'address' => 'New address',
        'combined_billing_enabled' => '1',
    ])->assertRedirect(route('families.show', $family));

    expect($family->refresh()->address)->toBe('New address');

    $event = Event::factory()->for($academicYear)->for($feeCategory)->create(['name' => 'Old Event']);

    $this->actingAs($admin)->put(route('events.update', $event), [
        'academic_year_id' => $academicYear->id,
        'fee_category_id' => $feeCategory->id,
        'name' => 'New Event',
        'event_date' => '2026-12-01',
        'is_mandatory' => '0',
    ])->assertRedirect(route('events.show', $event));

    $event->refresh();
    expect($event->name)->toBe('New Event');
    expect($event->is_mandatory)->toBeFalse();
});

it('logs out and blocks the admin dashboard afterwards', function () {
    $admin = adminUser();

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    $this->get(route('families.index'))->assertRedirect(route('login'));
});

it('completes the core admin journey from login through payment receipt and event dues', function () {
    $admin = adminUser();

    $this->post(route('login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect();

    $this->get(route('admin.dashboard'))->assertOk();

    $this->post(route('families.store'), [
        'family_code' => 'FAM-QA',
        'address' => '1 QA Road',
        'home_contact_no' => '0770000000',
        'combined_billing_enabled' => '1',
        'guardians' => [
            ['name' => 'QA Parent', 'relationship' => 'mother', 'email' => 'qa@example.test'],
        ],
    ])->assertRedirect();

    $family = Family::query()->sole();
    $guardian = $family->guardians->sole();
    expect($guardian->name)->toBe('QA Parent');

    $academicYear = AcademicYear::factory()->create(['name' => '2026/2027']);
    $nextYear = AcademicYear::factory()->create(['name' => '2027/2028']);
    setActiveAcademicYear($academicYear);
    $grade = Grade::factory()->create(['sequence_order' => 1, 'name' => 'Grade 1']);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2, 'name' => 'Grade 2']);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    Section::factory()->for($nextGrade)->create(['name' => 'A']);

    $this->post(route('families.students.store', $family), [
        'name' => 'QA Child',
        'dob' => '2015-06-07',
        'gender' => 'female',
        'admission_no' => 'ADM-QA-1',
        'guardian_ids' => [$guardian->id],
        'academic_year_id' => $academicYear->id,
        'grade_id' => $grade->id,
        'section_id' => $section->id,
    ])->assertRedirect();

    $student = Student::query()->sole();
    expect($student->status)->toBe(Student::STATUS_PENDING_REGISTRATION);
    expect($student->guardians)->toHaveCount(1);
    expect(Enrollment::where('academic_year_id', $academicYear->id)->count())->toBe(1);

    $this->post(route('fee-categories.store'), [
        'name' => 'Tuition',
        'is_recurring' => '1',
    ])->assertRedirect();

    $this->post(route('fee-categories.store'), [
        'name' => 'Transport',
        'is_opt_in' => '1',
    ])->assertRedirect();

    $tuition = FeeCategory::query()->where('name', 'Tuition')->sole();
    $transport = FeeCategory::query()->where('name', 'Transport')->sole();
    expect($tuition->is_recurring)->toBeTrue();
    expect($tuition->is_opt_in)->toBeFalse();
    expect($transport->is_opt_in)->toBeTrue();

    $this->post(route('fee-structures.store'), [
        'fee_category_id' => $tuition->id,
        'grade_id' => $grade->id,
        'academic_year_id' => $academicYear->id,
        'amount' => '100.00',
        'frequency' => 'monthly',
    ])->assertRedirect();

    $this->post(route('students.discounts.store', $student), [
        'fee_category_id' => $tuition->id,
        'type' => 'scholarship',
        'value' => 10,
        'value_type' => 'amount',
        'is_active' => '1',
    ])->assertRedirect();

    expect(Discount::query()->sole()->student_id)->toBe($student->id);

    $this->post(route('students.fee-subscriptions.store', $student), [
        'fee_category_id' => $transport->id,
        'academic_year_id' => $academicYear->id,
        'is_active' => '1',
    ])->assertRedirect();

    expect(StudentFeeSubscription::query()->sole()->is_active)->toBeTrue();

    $this->post(route('students.fee-subscriptions.store', $student), [
        'fee_category_id' => $tuition->id,
        'academic_year_id' => $academicYear->id,
    ])->assertSessionHasErrors('fee_category_id');

    expect(StudentFeeSubscription::count())->toBe(1);

    $this->post(route('due-generation.recurring.store'), [
        'academic_year_id' => $academicYear->id,
        'due_date' => '2026-10-10',
        'cycle_key' => '2026-10',
    ])->assertRedirect();

    $dueItem = StudentDueItem::query()->sole();
    expect($dueItem->original_amount)->toBe('100.00');
    expect($dueItem->discount_amount)->toBe('10.00');
    expect($dueItem->net_amount)->toBe('90.00');
    expect($dueItem->balance_amount)->toBe('90.00');

    $this->get(route('dues-dashboard.index'))->assertOk()->assertSee('90.00');

    $this->post(route('families.payments.store', $family), [
        'receipt_no' => 'RCT-QA-1',
        'method' => 'cash',
        'paid_at' => '2026-10-05',
        'amount' => '90.00',
        'allocations' => [
            ['student_due_item_id' => $dueItem->id, 'amount' => '90.00'],
        ],
    ])->assertRedirect();

    $dueItem->refresh();
    expect($dueItem->paid_amount)->toBe('90.00');
    expect($dueItem->balance_amount)->toBe('0.00');
    expect($dueItem->status)->toBe(StudentDueItem::STATUS_PAID);

    $payment = Payment::query()->sole();
    $receipt = Receipt::query()->sole();

    $this->get(route('payments.show', $payment))->assertOk()->assertSee('RCT-QA-1');
    $this->get(route('receipts.show', $receipt))->assertOk()->assertSee('RCT-QA-1');

    $this->post(route('events.store'), [
        'academic_year_id' => $academicYear->id,
        'fee_category_id' => $transport->id,
        'name' => 'QA Event',
        'event_date' => '2026-11-01',
        'is_mandatory' => '1',
    ])->assertRedirect();

    $event = Event::query()->sole();

    $this->post(route('events.charges.store', $event), [
        'grade_id' => $grade->id,
        'amount' => '15.00',
    ])->assertRedirect();

    $this->post(route('due-generation.events.store'), ['event_id' => $event->id])->assertRedirect();

    expect(StudentDueItem::count())->toBe(2);

    $this->post(route('payment-reminders.store'), [
        'as_of_date' => '2026-11-01',
        'upcoming_window_days' => 7,
    ])->assertRedirect();

    expect(PaymentReminder::query()->count())->toBeGreaterThan(0);

    $this->get(route('payment-reminders.index'))->assertOk();
    $this->get(route('payment-reminders.show', PaymentReminder::query()->sole()))->assertOk();

    setActiveAcademicYear($nextYear);

    $this->post(route('promotion-batches.store'), [
        'source_academic_year_id' => $academicYear->id,
        'target_academic_year_id' => $nextYear->id,
        'source_section_ids' => [$section->id],
    ])->assertRedirect();

    $batch = PromotionBatch::query()->sole();

    $this->get(route('promotion-batches.show', $batch))->assertOk();

    // Promotion only lists active students. This student is still pending_registration
    // because payment-driven activation is deferred, so the batch is intentionally empty.
    expect($batch->items()->count())->toBe(0);

    $this->post(route('promotion-batches.confirm', $batch))->assertRedirect();

    expect(Enrollment::where('academic_year_id', $nextYear->id)->count())->toBe(0);
    expect(StudentDueItem::where('academic_year_id', $nextYear->id)->count())->toBe(0);
    expect(Payment::count())->toBe(1);
    expect(Receipt::count())->toBe(1);
});
