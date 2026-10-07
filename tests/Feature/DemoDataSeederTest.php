<?php

use App\Models\AcademicYear;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventCharge;
use App\Models\EventParticipation;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentReminder;
use App\Models\PromotionBatch;
use App\Models\Receipt;
use App\Models\Role;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDueItem;
use App\Models\StudentFeeSubscription;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function seedDemoData($test): void
{
    $test->seed(DemoDataSeeder::class);
}

it('creates the demo admin user with the admin role', function () {
    seedDemoData($this);

    $admin = User::query()->where('email', 'admin@skooly.test')->sole();

    expect($admin->hasRole(Role::ADMIN))->toBeTrue();
    expect($admin->name)->toBe('Demo Admin');
});

it('creates the accountant and teacher demo users for access testing', function () {
    seedDemoData($this);

    expect(User::query()->where('email', 'accountant@skooly.test')->sole()->hasRole(Role::ACCOUNTANT))->toBeTrue();
    expect(User::query()->where('email', 'teacher@skooly.test')->sole()->hasRole(Role::TEACHER))->toBeTrue();
});

it('creates the fixed roles', function () {
    seedDemoData($this);

    expect(Role::query()->count())->toBe(4);
    expect(Role::query()->pluck('name')->sort()->values()->all())
        ->toBe([Role::ACCOUNTANT, Role::ADMIN, Role::SUPERADMIN, Role::TEACHER]);
});

it('creates academic years grades and sections', function () {
    seedDemoData($this);

    expect(AcademicYear::query()->pluck('name')->sort()->values()->all())
        ->toBe(['2026/2027', '2027/2028']);
    expect(Grade::query()->count())->toBe(2);
    expect(Section::query()->count())->toBe(2);
});

it('creates demo families students and explicit guardian links', function () {
    seedDemoData($this);

    expect(Family::query()->count())->toBe(2);
    expect(Student::query()->count())->toBe(3);
    expect(Guardian::query()->count())->toBe(3);

    $chloe = Student::query()->where('admission_no', 'ADM-DEMO-001')->sole();
    $liam = Student::query()->where('admission_no', 'ADM-DEMO-002')->sole();
    $mia = Student::query()->where('admission_no', 'ADM-DEMO-003')->sole();

    expect($chloe->status)->toBe(Student::STATUS_ACTIVE);
    expect($liam->status)->toBe(Student::STATUS_ACTIVE);
    expect($mia->status)->toBe(Student::STATUS_PENDING_REGISTRATION);

    // Bob is linked only to Liam, so guardian privacy is demonstrable on the demo data.
    $bob = Guardian::query()->where('name', 'Bob Demo')->sole();
    expect($bob->students()->pluck('id')->all())->toBe([$liam->id]);

    $alice = Guardian::query()->where('name', 'Alice Demo')->sole();
    expect($alice->students()->count())->toBe(2);

    expect(Enrollment::query()->count())->toBe(3);
});

it('creates fee categories structures discounts and a subscription', function () {
    seedDemoData($this);

    expect(FeeCategory::query()->pluck('name')->sort()->values()->all())
        ->toBe(['Event Fees', 'Transport', 'Tuition']);

    $transport = FeeCategory::query()->where('name', 'Transport')->sole();
    $tuition = FeeCategory::query()->where('name', 'Tuition')->sole();

    expect($transport->is_opt_in)->toBeTrue();
    expect($transport->is_recurring)->toBeTrue();
    expect($tuition->is_recurring)->toBeTrue();
    expect($tuition->is_opt_in)->toBeFalse();

    expect(FeeStructure::query()->count())->toBe(3);

    $chloe = Student::query()->where('admission_no', 'ADM-DEMO-001')->sole();
    expect(Discount::query()->where('student_id', $chloe->id)->sole()->value)->toBe('10.00');
    expect(StudentFeeSubscription::query()->where('student_id', $chloe->id)->sole()->is_active)->toBeTrue();
});

it('generates recurring due items through the existing action', function () {
    seedDemoData($this);

    $dueItems = StudentDueItem::query()->get();

    expect($dueItems)->not->toBeEmpty();
    $dueItems->each(function (StudentDueItem $dueItem): void {
        expect($dueItem->balance_amount)->not->toBeNull();
        expect($dueItem->generation_key)->not->toBeNull();
    });

    // The scholarship discount must be reflected at generation time.
    $discounted = $dueItems->firstWhere('description', 'Tuition');
    expect($discounted->discount_amount)->toBe('10.00');
    expect($discounted->net_amount)->toBe('90.00');
});

it('creates a payment receipt and reminder through existing actions', function () {
    seedDemoData($this);

    $payment = Payment::query()->sole();
    $receipt = Receipt::query()->sole();

    expect($payment->amount)->not->toBeNull();
    expect($receipt->receipt_no)->toBe('DEMO-REC-001');
    expect($receipt->payment_id)->toBe($payment->id);
    expect($receipt->family_snapshot)->toHaveKey('family_code');

    expect(PaymentReminder::query()->count())->toBeGreaterThan(0);
    expect(PaymentReminder::query()->whereNotNull('sent_at')->count())->toBe(0);
});

it('creates a demo event with a charge and generated event dues', function () {
    seedDemoData($this);

    $event = Event::query()->where('name', 'Demo Sports Day')->sole();

    expect(EventCharge::query()->where('event_id', $event->id)->sole()->amount)->toBe('25.00');
    expect(EventParticipation::query()->where('event_id', $event->id)->count())->toBeGreaterThan(0);
    expect($event->eventDueItems()->count())->toBeGreaterThan(0);
    expect(StudentDueItem::query()->where('description', 'Event: Demo Sports Day')->count())
        ->toBe($event->eventDueItems()->count());
});

it('creates a draft promotion batch ready for manual testing', function () {
    seedDemoData($this);

    $batch = PromotionBatch::query()->sole();

    expect($batch->status)->toBe(PromotionBatch::STATUS_DRAFT);
    expect($batch->confirmed_at)->toBeNull();
    expect($batch->items()->count())->toBeGreaterThan(0);
    expect($batch->items()->where('action', 'promote')->count())->toBeGreaterThan(0);
});

it('is idempotent when run twice', function () {
    seedDemoData($this);

    $counts = [
        User::count(),
        Family::count(),
        Student::count(),
        Guardian::count(),
        FeeCategory::count(),
        FeeStructure::count(),
        StudentDueItem::count(),
        Payment::count(),
        Receipt::count(),
        PaymentReminder::count(),
        Event::count(),
        PromotionBatch::count(),
        Discount::count(),
        StudentFeeSubscription::count(),
        Enrollment::count(),
    ];

    seedDemoData($this);

    expect([
        User::count(),
        Family::count(),
        Student::count(),
        Guardian::count(),
        FeeCategory::count(),
        FeeStructure::count(),
        StudentDueItem::count(),
        Payment::count(),
        Receipt::count(),
        PaymentReminder::count(),
        Event::count(),
        PromotionBatch::count(),
        Discount::count(),
        StudentFeeSubscription::count(),
        Enrollment::count(),
    ])->toBe($counts);

    expect(User::query()->where('email', 'admin@skooly.test')->count())->toBe(1);
    expect(Role::query()->where('name', Role::ADMIN)->count())->toBe(1);
    expect(Receipt::query()->where('receipt_no', 'DEMO-REC-001')->count())->toBe(1);
});

it('refuses to seed demo data in the production environment', function () {
    app()['env'] = 'production';
    $this->withoutMockingConsoleOutput();

    try {
        seedDemoData($this);

        expect(User::query()->where('email', 'admin@skooly.test')->count())->toBe(0);
        expect(Family::query()->count())->toBe(0);
    } finally {
        app()['env'] = 'testing';
    }
});

it('lets the admin dashboard and main web pages load after seeding', function () {
    seedDemoData($this);
    $admin = User::query()->where('email', 'admin@skooly.test')->sole();

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Families')->assertSee('Draft promotion batches');
    $this->actingAs($admin)->get(route('families.index'))->assertOk();
    $this->actingAs($admin)->get(route('dues-dashboard.index'))->assertOk();
    $this->actingAs($admin)->get(route('payment-reminders.index'))->assertOk();
    $this->actingAs($admin)->get(route('events.index'))->assertOk()->assertSee('Demo Sports Day');
    $this->actingAs($admin)->get(route('promotion-batches.index'))->assertOk();
    $this->actingAs($admin)->get(route('families.payments.create', Family::query()->where('family_code', 'FAM-DEMO-01')->sole()))->assertOk();
});
