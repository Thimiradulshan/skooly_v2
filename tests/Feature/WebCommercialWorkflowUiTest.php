<?php

use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\Family;
use App\Models\Payment;
use App\Models\PaymentReminder;
use App\Models\PromotionBatch;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    setActiveAcademicYear(AcademicYear::factory()->create());
});

it('shows workflow cards and primary links on the dashboard', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Registration')
        ->assertSee('Fees &amp; dues', false)
        ->assertSee('Payments')
        ->assertSee('Events &amp; promotion', false)
        ->assertSee('Reminders')
        ->assertSee(route('families.create', [], false), false)
        ->assertSee(route('due-generation.recurring.create', [], false), false)
        ->assertSee(route('dues-dashboard.index', [], false), false)
        ->assertSee(route('promotion-batches.index', [], false), false)
        ->assertSee(route('payment-reminders.index', [], false), false);
});

it('gives the family index a create family action', function () {
    $this->actingAs(adminUser())
        ->get(route('families.index'))
        ->assertOk()
        ->assertSee('Create family')
        ->assertSee(route('families.create', [], false), false);
});

it('exposes edit, register student, and record payment on a family', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();

    $this->actingAs(adminUser())
        ->get(route('families.show', $family))
        ->assertOk()
        ->assertSee('Edit family')
        ->assertSee('Register student')
        ->assertSee('Record payment')
        ->assertSee(route('families.edit', $family, false), false)
        ->assertSee(route('families.students.create', $family, false), false)
        ->assertSee(route('families.payments.create', $family, false), false)
        ->assertSee(route('students.discounts.create', $student, false), false)
        ->assertSee(route('students.fee-subscriptions.create', $student, false), false);
});

it('renders printable family and student detail records', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();

    $this->actingAs(adminUser())
        ->get(route('families.show', $family))
        ->assertOk()
        ->assertSee('Print family details')
        ->assertSee('data-testid="family-print-button"', false)
        ->assertSee('onclick="window.print()"', false)
        ->assertSee('print-record', false);

    $this->actingAs(adminUser())
        ->get(route('students.show', $student))
        ->assertOk()
        ->assertSee('Print student details')
        ->assertSee('data-testid="student-print-button"', false)
        ->assertSee('onclick="window.print()"', false)
        ->assertSee('print-record', false)
        ->assertSee('print-hide', false);

    $printStylesheet = file_get_contents(public_path('css/admin.css'));

    expect($printStylesheet)
        ->toContain('@media print')
        ->toContain('.print-record .actions')
        ->toContain('.print-record .print-hide')
        ->toContain('.sidebar-toggle-control');
});

it('gives the fee category index a create action and edit rows', function () {
    $this->actingAs(adminUser())
        ->get(route('fee-categories.index'))
        ->assertOk()
        ->assertSee('Create fee category')
        ->assertSee(route('fee-categories.create', [], false), false)
        ->assertSee(route('fee-structures.index', [], false), false);
});

it('gives the fee structure index a create action', function () {
    $this->actingAs(adminUser())
        ->get(route('fee-structures.index'))
        ->assertOk()
        ->assertSee('Create fee structure')
        ->assertSee(route('fee-structures.create', [], false), false);
});

it('marks the due generation pages with confirmation and dashboard links', function () {
    Event::factory()->for(AcademicYear::query()->sole())->create();

    $this->actingAs(adminUser())
        ->get(route('due-generation.recurring.create'))
        ->assertOk()
        ->assertSee('data-confirm', false)
        ->assertSee('data-confirm-title="Generate recurring dues"', false)
        ->assertSee('data-loading', false)
        ->assertSee(route('dues-dashboard.index', [], false), false)
        ->assertSee(route('fee-structures.index', [], false), false);

    $this->actingAs(adminUser())
        ->get(route('due-generation.events.create'))
        ->assertOk()
        ->assertSee('data-confirm', false)
        ->assertSee('data-confirm-title="Generate event dues"', false)
        ->assertSee('data-loading', false)
        ->assertSee(route('events.index', [], false), false);
});

it('explains manual allocation and marks the payment form for confirmation', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    StudentDueItem::factory()->for($student)->create([
        'balance_amount' => 100,
        'net_amount' => 100,
    ]);

    $this->actingAs(adminUser())
        ->get(route('families.payments.create', $family))
        ->assertOk()
        ->assertSee('Allocation is entirely manual')
        ->assertSee('Manual allocations')
        ->assertSee('Must be unique')
        ->assertSee('data-confirm', false)
        ->assertSee('data-confirm-title="Record payment"', false)
        ->assertSee('data-confirm-tone="danger"', false)
        ->assertSee('data-loading', false)
        ->assertSee('Record manual payment')
        ->assertSee('Cancel');
});

it('links a payment to its receipt and back to the family', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = StudentDueItem::factory()->for($student)->create([
        'balance_amount' => 100,
        'net_amount' => 100,
    ]);

    $payment = Payment::recordManual(
        $family,
        'RCT-WORKFLOW-1',
        'cash',
        '100.00',
        [['student_due_item_id' => $dueItem->id, 'amount' => '100.00']],
    );

    $this->actingAs(adminUser())
        ->get(route('payments.show', $payment))
        ->assertOk()
        ->assertSee('View receipt')
        ->assertSee(route('receipts.show', $payment->receipt, false), false)
        ->assertSee(route('families.show', $family, false), false);
});

it('renders a receipt preview with a clear total and no export claim', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = StudentDueItem::factory()->for($student)->create([
        'balance_amount' => 100,
        'net_amount' => 100,
    ]);

    $payment = Payment::recordManual(
        $family,
        'RCT-WORKFLOW-2',
        'cash',
        '100.00',
        [['student_due_item_id' => $dueItem->id, 'amount' => '100.00']],
    );
    $receipt = $payment->receipt;

    $this->actingAs(adminUser())
        ->get(route('receipts.show', $receipt))
        ->assertOk()
        ->assertSee('data-testid="receipt-preview"', false)
        ->assertSee('Total received')
        ->assertSee('Skooly')
        ->assertSee('not recalculated from live due items', false)
        ->assertSee('PDF export is not implemented', false)
        ->assertSee(route('payments.show', $payment, false), false);
});

it('gives the event index a create action', function () {
    $this->actingAs(adminUser())
        ->get(route('events.index'))
        ->assertOk()
        ->assertSee('Create event')
        ->assertSee(route('events.create', [], false), false);
});

it('exposes edit, charge, participation, and generation on an event', function () {
    $event = Event::factory()->create();

    $this->actingAs(adminUser())
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Edit event')
        ->assertSee('Add charge')
        ->assertSee('Manage participation')
        ->assertSee('Generate event dues')
        ->assertSee('Editing an event never creates or changes due items.', false)
        ->assertSee(route('events.edit', $event, false), false)
        ->assertSee(route('events.charges.create', $event, false), false)
        ->assertSee(route('events.participation.create', $event, false), false)
        ->assertSee(route('due-generation.events.create', false), false);
});

it('gives the promotion index a create batch action', function () {
    $this->actingAs(adminUser())
        ->get(route('promotion-batches.index'))
        ->assertOk()
        ->assertSee('Create promotion batch')
        ->assertSee(route('promotion-batches.create', [], false), false);
});

it('offers a confirmed confirm action only while a batch is draft', function () {
    $draft = PromotionBatch::factory()->create(['status' => PromotionBatch::STATUS_DRAFT]);

    $this->actingAs(adminUser())
        ->get(route('promotion-batches.show', $draft))
        ->assertOk()
        ->assertSee('Confirm promotion batch')
        ->assertSee('data-confirm-title="Confirm promotion batch"', false)
        ->assertSee('data-confirm-tone="danger"', false)
        ->assertSee('data-loading', false)
        ->assertSee('never changes source-year enrollments', false)
        ->assertSee('does not create any fee items', false);

    $confirmed = PromotionBatch::factory()->create(['status' => PromotionBatch::STATUS_CONFIRMED]);

    $this->actingAs(adminUser())
        ->get(route('promotion-batches.show', $confirmed))
        ->assertOk()
        ->assertDontSee('Confirm promotion batch')
        ->assertDontSee('data-confirm-title="Confirm promotion batch"', false);
});

it('gives the reminder index a generate action', function () {
    $this->actingAs(adminUser())
        ->get(route('payment-reminders.index'))
        ->assertOk()
        ->assertSee('Generate reminder records')
        ->assertSee(route('payment-reminders.create', [], false), false);
});

it('marks the reminder form and detail as outbox only, never sent', function () {
    $this->actingAs(adminUser())
        ->get(route('payment-reminders.create'))
        ->assertOk()
        ->assertSee('internal outbox records', false)
        ->assertSee('data-confirm-title="Generate reminders"', false)
        ->assertSee('data-loading', false);

    $reminder = PaymentReminder::factory()->create();

    $this->actingAs(adminUser())
        ->get(route('payment-reminders.show', $reminder))
        ->assertOk()
        ->assertSee('data-testid="reminder-not-sent"', false)
        ->assertSee('Preview only', false)
        ->assertSee('no email, SMS, or WhatsApp provider is connected', false)
        ->assertSee('never marked as sent', false)
        ->assertDontSee('Send reminder', false);
});

it('shows family and student context on registration, discount, and subscription pages', function () {
    $family = Family::factory()->create(['family_code' => 'FAM-CRUMB']);
    $student = Student::factory()->for($family)->create();

    $this->actingAs(adminUser())->get(route('families.students.create', $family))
        ->assertOk()->assertSee('FAM-CRUMB')->assertSee('breadcrumb', false);

    $this->actingAs(adminUser())->get(route('students.discounts.create', $student))
        ->assertOk()->assertSee($student->name)->assertSee('FAM-CRUMB');

    $this->actingAs(adminUser())->get(route('students.fee-subscriptions.create', $student))
        ->assertOk()->assertSee($student->name)->assertSee('FAM-CRUMB');
});

it('shows empty index states with a next action', function () {
    $admin = adminUser();

    foreach ([
        route('families.index') => 'Create family',
        route('fee-categories.index') => 'Create fee category',
        route('fee-structures.index') => 'Create fee structure',
        route('events.index') => 'Create event',
        route('promotion-batches.index') => 'Create promotion batch',
        route('payment-reminders.index') => 'Generate reminders',
    ] as $url => $action) {
        $this->actingAs($admin)->get($url)->assertOk()->assertSee('empty-state-title', false)->assertSee($action);
    }
});

it('loads the local admin ui script without a build step', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('js/admin-ui.js', false);

    expect(file_exists(public_path('js/admin-ui.js')))->toBeTrue();
    expect(file_exists(public_path('css/admin.css')))->toBeTrue();
});

it('still renders every existing workflow page', function () {
    $admin = adminUser();
    $family = Family::factory()->create();
    Student::factory()->for($family)->create();

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

it('adds no destructive routes or fake send actions', function () {
    foreach ([
        'families.destroy', 'students.destroy', 'payments.destroy', 'payments.refund',
        'receipts.destroy', 'events.destroy', 'promotion-batches.destroy',
        'payment-reminders.send', 'payment-reminders.destroy',
    ] as $name) {
        expect(Route::has($name))->toBeFalse();
    }
});
