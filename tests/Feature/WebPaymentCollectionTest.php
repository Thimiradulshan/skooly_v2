<?php

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Receipt;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

function paymentDueItem(Student $student, array $attributes = []): StudentDueItem
{
    return StudentDueItem::factory()
        ->for($student)
        ->for(AcademicYear::factory())
        ->for(FeeCategory::factory())
        ->create(array_merge([
            'description' => 'Test due item',
            'original_amount' => 100,
            'net_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => StudentDueItem::STATUS_UNPAID,
            'due_date' => '2026-10-10',
        ], $attributes));
}

function manualPaymentPayload(StudentDueItem $dueItem, array $overrides = []): array
{
    return array_merge([
        'receipt_no' => 'RCT-WEB-'.fake()->unique()->numerify('#####'),
        'method' => 'cash',
        'paid_at' => '2026-10-05',
        'amount' => '40.00',
        'allocations' => [
            ['student_due_item_id' => $dueItem->id, 'amount' => '40.00'],
        ],
    ], $overrides);
}

it('denies guests the payment collection page', function () {
    $this->get(route('families.payments.create', Family::factory()->create()))
        ->assertRedirect(route('login'));
});

it('denies guests the payment and receipt history pages', function () {
    $this->get(route('payments.index'))->assertRedirect(route('login'));
    $this->get(route('receipts.index'))->assertRedirect(route('login'));
    $this->get(route('receipts.pdf', Receipt::factory()->create()))->assertRedirect(route('login'));
});

it('denies teachers and accountants receipt PDF downloads', function () {
    $receipt = Receipt::factory()->create();

    $this->actingAs(userWithRole(Role::TEACHER))
        ->get(route('receipts.pdf', $receipt))
        ->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))
        ->get(route('receipts.pdf', $receipt))
        ->assertForbidden();
});

it('lets an accountant select a family by code and record its payment', function () {
    $family = Family::factory()->create();
    $dueItem = paymentDueItem(Student::factory()->for($family)->create(['name' => 'Collection student']));

    $this->actingAs(userWithRole(Role::ACCOUNTANT))
        ->get(route('payments.collect'))
        ->assertOk()
        ->assertSee('Family code');

    $this->actingAs(userWithRole(Role::ACCOUNTANT))
        ->get(route('families.payments.create', $family))
        ->assertForbidden();

    $this->actingAs(userWithRole(Role::ACCOUNTANT))
        ->get(route('payments.collect', ['family_code' => $family->family_code]))
        ->assertRedirect(route('families.payments.create', $family));

    $this->actingAs(userWithRole(Role::ACCOUNTANT))
        ->get(route('families.payments.create', $family))
        ->assertOk()
        ->assertSee('Collection student')
        ->assertSee('100.00')
        ->assertDontSee('Back to family')
        ->assertDontSee('Generate recurring dues');

    $otherFamily = Family::factory()->create();

    $this->actingAs(userWithRole(Role::ACCOUNTANT))
        ->get(route('families.payments.create', $otherFamily))
        ->assertForbidden();

    $this->actingAs(userWithRole(Role::ACCOUNTANT))
        ->post(route('families.payments.store', $family), manualPaymentPayload($dueItem))
        ->assertRedirect();

    expect(Payment::query()->sole()->family_id)->toBe($family->id);
    expect($dueItem->refresh()->balance_amount)->toBe('60.00');
});

it('denies teachers the payment collection entry and recording routes', function () {
    $family = Family::factory()->create();

    $this->actingAs(userWithRole(Role::TEACHER))
        ->get(route('payments.collect'))
        ->assertForbidden();
    $this->actingAs(userWithRole(Role::TEACHER))
        ->get(route('families.payments.create', $family))
        ->assertForbidden();
    $this->actingAs(userWithRole(Role::TEACHER))
        ->post(route('families.payments.store', $family), [])
        ->assertForbidden();
});

it('shows only outstanding due items for the selected family', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create(['name' => 'Family Child']);
    paymentDueItem($student, ['description' => 'Outstanding item']);
    paymentDueItem($student, [
        'description' => 'Paid item',
        'paid_amount' => 100,
        'balance_amount' => 0,
        'status' => StudentDueItem::STATUS_PAID,
    ]);
    $otherStudent = Student::factory()->for(Family::factory())->create();
    paymentDueItem($otherStudent, ['description' => 'Other family item']);

    $this->actingAs(adminUser())
        ->get(route('families.payments.create', $family))
        ->assertOk()
        ->assertSee('Family Child')
        ->assertSee('Outstanding item')
        ->assertDontSee('Paid item')
        ->assertDontSee('Other family item');
});

it('records a manual payment for one due item through the web route', function () {
    $admin = adminUser();
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = paymentDueItem($student);

    $this->actingAs($admin)
        ->post(route('families.payments.store', $family), manualPaymentPayload($dueItem))
        ->assertRedirect();

    $payment = Payment::query()->sole();

    expect($payment->allocations)->toHaveCount(1);
    expect($dueItem->refresh()->paid_amount)->toBe('40.00');
    expect($dueItem->balance_amount)->toBe('60.00');
    expect($dueItem->status)->toBe(StudentDueItem::STATUS_PARTIALLY_PAID);
    expect(Receipt::where('payment_id', $payment->id)->count())->toBe(1);
    expect(AuditLog::where('action', AuditLog::ACTION_PAYMENT_RECORDED)->count())->toBe(1);
    expect(AuditLog::where('action', AuditLog::ACTION_PAYMENT_ALLOCATION_RECORDED)->count())->toBe(1);
});

it('records a manual payment across multiple due items', function () {
    $family = Family::factory()->create();
    $firstStudent = Student::factory()->for($family)->create();
    $secondStudent = Student::factory()->for($family)->create();
    $firstDueItem = paymentDueItem($firstStudent, [
        'original_amount' => 40,
        'net_amount' => 40,
        'balance_amount' => 40,
    ]);
    $secondDueItem = paymentDueItem($secondStudent, [
        'original_amount' => 60,
        'net_amount' => 60,
        'balance_amount' => 60,
    ]);

    $this->actingAs(adminUser())
        ->post(route('families.payments.store', $family), [
            'receipt_no' => 'RCT-WEB-MULTI',
            'method' => 'cash',
            'paid_at' => '2026-10-05',
            'amount' => '100.00',
            'allocations' => [
                ['student_due_item_id' => $firstDueItem->id, 'amount' => '40.00'],
                ['student_due_item_id' => $secondDueItem->id, 'amount' => '60.00'],
            ],
        ])
        ->assertRedirect();

    expect(Payment::query()->sole()->allocations)->toHaveCount(2);
    expect($firstDueItem->refresh()->status)->toBe(StudentDueItem::STATUS_PAID);
    expect($secondDueItem->refresh()->status)->toBe(StudentDueItem::STATUS_PAID);
    expect($firstDueItem->balance_amount)->toBe('0.00');
    expect($secondDueItem->balance_amount)->toBe('0.00');
});

it('validates that allocations total the payment amount', function () {
    $family = Family::factory()->create();
    $dueItem = paymentDueItem(Student::factory()->for($family)->create());

    $this->actingAs(adminUser())
        ->post(route('families.payments.store', $family), manualPaymentPayload($dueItem, [
            'amount' => '100.00',
        ]))
        ->assertSessionHasErrors('allocations');

    expect(Payment::count())->toBe(0);
    expect($dueItem->refresh()->balance_amount)->toBe('100.00');
});

it('validates that an allocation cannot exceed the due item balance', function () {
    $family = Family::factory()->create();
    $dueItem = paymentDueItem(Student::factory()->for($family)->create());

    $this->actingAs(adminUser())
        ->post(route('families.payments.store', $family), manualPaymentPayload($dueItem, [
            'amount' => '110.00',
            'allocations' => [
                ['student_due_item_id' => $dueItem->id, 'amount' => '110.00'],
            ],
        ]))
        ->assertSessionHasErrors('allocations.0.amount');

    expect(Payment::count())->toBe(0);
    expect($dueItem->refresh()->balance_amount)->toBe('100.00');
});

it('rejects allocation of another familys due item', function () {
    $family = Family::factory()->create();
    $otherDueItem = paymentDueItem(Student::factory()->for(Family::factory())->create());

    $this->actingAs(adminUser())
        ->post(route('families.payments.store', $family), manualPaymentPayload($otherDueItem))
        ->assertSessionHasErrors('allocations.0.student_due_item_id');

    expect(Payment::count())->toBe(0);
    expect($otherDueItem->refresh()->balance_amount)->toBe('100.00');
});

it('shows a receipt snapshot without recalculating changed due items', function () {
    $family = Family::factory()->create(['family_code' => 'FAM-RECEIPT']);
    $student = Student::factory()->for($family)->create();
    $dueItem = paymentDueItem($student, ['description' => 'Original tuition']);

    $this->actingAs(adminUser())
        ->post(route('families.payments.store', $family), manualPaymentPayload($dueItem, [
            'receipt_no' => 'RCT-WEB-SNAPSHOT',
            'amount' => '100.00',
            'allocations' => [
                ['student_due_item_id' => $dueItem->id, 'amount' => '100.00'],
            ],
        ]));

    $receipt = Receipt::query()->sole();
    $dueItem->update(['description' => 'Changed tuition', 'original_amount' => 200]);

    $this->actingAs(adminUser())
        ->get(route('receipts.show', $receipt))
        ->assertOk()
        ->assertSee('FAM-RECEIPT')
        ->assertSee('Original tuition')
        ->assertDontSee('Changed tuition')
        ->assertSee('100.00');
});

it('downloads an immutable receipt snapshot as a PDF for an admin', function () {
    $family = Family::factory()->create(['family_code' => 'FAM-PDF-SNAPSHOT']);
    $dueItem = paymentDueItem(Student::factory()->for($family)->create(['name' => 'Snapshot student']), [
        'description' => 'Original PDF tuition',
    ]);
    $payment = Payment::recordManual($family, 'RCT-PDF-SNAPSHOT', 'cash', '100.00', [
        ['student_due_item_id' => $dueItem->id, 'amount' => '100.00'],
    ]);
    $receipt = $payment->receipt;
    $snapshot = $receipt->only([
        'receipt_no', 'issued_at', 'family_snapshot', 'payment_snapshot', 'allocation_snapshot', 'total_amount',
    ]);
    $dueItem->update(['description' => 'Changed PDF tuition', 'original_amount' => 200]);
    $pdfHtml = view('receipts.pdf', ['receipt' => $receipt])->render();

    $response = $this->actingAs(adminUser())->get(route('receipts.pdf', $receipt));

    $response
        ->assertOk()
        ->assertDownload('RCT-PDF-SNAPSHOT.pdf')
        ->assertHeader('Content-Type', 'application/pdf');

    expect($response->getContent())
        ->toStartWith('%PDF');
    expect($pdfHtml)
        ->toContain('RCT-PDF-SNAPSHOT')
        ->toContain('FAM-PDF-SNAPSHOT')
        ->toContain('Original PDF tuition')
        ->not->toContain('Changed PDF tuition');
    expect($receipt->refresh()->only([
        'receipt_no', 'issued_at', 'family_snapshot', 'payment_snapshot', 'allocation_snapshot', 'total_amount',
    ]))->toEqual($snapshot);

    $this->actingAs(adminUser())
        ->get(route('receipts.show', $receipt))
        ->assertSee('Download PDF')
        ->assertSee(route('receipts.pdf', $receipt, false), false);
    $this->actingAs(userWithRole(Role::ACCOUNTANT))
        ->get(route('receipts.show', $receipt))
        ->assertDontSee('Download PDF');
});

it('shows payment details with a receipt link', function () {
    $family = Family::factory()->create();
    $dueItem = paymentDueItem(Student::factory()->for($family)->create());
    $payment = Payment::recordManual($family, 'RCT-WEB-SHOW', 'cash', '100.00', [
        ['student_due_item_id' => $dueItem->id, 'amount' => '100.00'],
    ]);

    $this->actingAs(adminUser())
        ->get(route('payments.show', $payment))
        ->assertOk()
        ->assertSee('RCT-WEB-SHOW')
        ->assertSee('Test due item')
        ->assertSee('View receipt');
});

it('lists payments and receipts with search sorting and pagination', function () {
    $firstFamily = Family::factory()->create(['family_code' => 'FAM-HISTORY-ONE']);
    $secondFamily = Family::factory()->create(['family_code' => 'FAM-HISTORY-TWO']);
    $firstDueItem = paymentDueItem(Student::factory()->for($firstFamily)->create());
    $secondDueItem = paymentDueItem(Student::factory()->for($secondFamily)->create());
    $firstPayment = Payment::recordManual($firstFamily, 'RCT-HISTORY-ONE', 'cash', '20.00', [
        ['student_due_item_id' => $firstDueItem->id, 'amount' => '20.00'],
    ], 'PAY-HISTORY-ONE', paidAt: now()->subDay());
    Payment::recordManual($secondFamily, 'RCT-HISTORY-TWO', 'card', '40.00', [
        ['student_due_item_id' => $secondDueItem->id, 'amount' => '40.00'],
    ], 'PAY-HISTORY-TWO');

    $this->actingAs(adminUser())
        ->get(route('payments.index', ['search' => 'HISTORY-ONE', 'sort' => 'amount', 'direction' => 'asc']))
        ->assertOk()
        ->assertSee('FAM-HISTORY-ONE')
        ->assertSee('RCT-HISTORY-ONE')
        ->assertDontSee('FAM-HISTORY-TWO');

    $this->actingAs(adminUser())
        ->get(route('receipts.index', ['search' => 'PAY-HISTORY-ONE']))
        ->assertOk()
        ->assertSee('RCT-HISTORY-ONE')
        ->assertDontSee('RCT-HISTORY-TWO');

    expect($firstPayment->receipt)->not->toBeNull();
});

it('rejects invalid list sorting input', function () {
    $this->actingAs(adminUser())
        ->get(route('payments.index', ['sort' => 'payments.amount; drop table payments']))
        ->assertSessionHasErrors('sort');
});

it('adds no automatic allocation, receipt mutation, payment edit, delete, or refund route', function () {
    expect(Route::has('payments.index'))->toBeTrue();
    expect(Route::has('payments.collect'))->toBeTrue();
    expect(Route::has('payments.collect.store'))->toBeFalse();
    expect(Route::has('receipts.index'))->toBeTrue();
    expect(Route::has('payments.edit'))->toBeFalse();
    expect(Route::has('payments.update'))->toBeFalse();
    expect(Route::has('payments.destroy'))->toBeFalse();
    expect(Route::has('payments.refund'))->toBeFalse();
    expect(Route::has('receipts.create'))->toBeFalse();
    expect(Route::has('receipts.store'))->toBeFalse();
    expect(Route::has('receipts.edit'))->toBeFalse();
    expect(Route::has('receipts.update'))->toBeFalse();
    expect(Route::has('receipts.destroy'))->toBeFalse();

    $family = Family::factory()->create();
    $dueItem = paymentDueItem(Student::factory()->for($family)->create());
    $payment = Payment::recordManual($family, 'RCT-WEB-NOAUTO', 'cash', '100.00', [
        ['student_due_item_id' => $dueItem->id, 'amount' => '100.00'],
    ]);

    expect($payment->allocations)->toHaveCount(1);
    expect($payment->allocations->sole()->student_due_item_id)->toBe($dueItem->id);
    expect(PaymentAllocation::count())->toBe(1);

    $this->actingAs(adminUser())
        ->delete(route('payments.show', $payment))
        ->assertMethodNotAllowed();
    $this->actingAs(adminUser())
        ->post(route('payments.collect'), [])
        ->assertMethodNotAllowed();
});
