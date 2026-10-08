<?php

use App\Actions\Reports\BuildDuesDashboardReport;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\Grade;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Receipt;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use InvalidArgumentException;

uses(LazilyRefreshDatabase::class);

function reportStudent(AcademicYear $academicYear, Grade $grade, Section $section, ?Family $family = null): Student
{
    $student = Student::factory()->for($family ?? Family::factory())->create();

    Enrollment::factory()
        ->for($student)
        ->for($academicYear)
        ->for($grade)
        ->for($section)
        ->create();

    return $student;
}

function dueItem(Student $student, AcademicYear $academicYear, FeeCategory $feeCategory, array $attributes = []): StudentDueItem
{
    if (! array_key_exists('net_amount', $attributes) && (array_key_exists('paid_amount', $attributes) || array_key_exists('balance_amount', $attributes))) {
        $attributes['net_amount'] = (float) ($attributes['paid_amount'] ?? 0) + (float) ($attributes['balance_amount'] ?? 100);
    }

    if (array_key_exists('net_amount', $attributes) && ! array_key_exists('balance_amount', $attributes)) {
        $attributes['balance_amount'] = (float) $attributes['net_amount'] - (float) ($attributes['paid_amount'] ?? 0);
    }

    return StudentDueItem::factory()->for($student)->for($academicYear)->for($feeCategory)->create($attributes);
}

function duesReport(array $filters = []): array
{
    return app(BuildDuesDashboardReport::class)->handle($filters);
}

it('reports total due paid and outstanding from stored snapshots', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $student = reportStudent($academicYear, $grade, $section);
    $feeCategory = FeeCategory::factory()->create();

    dueItem($student, $academicYear, $feeCategory, [
        'original_amount' => 100,
        'discount_amount' => 10,
        'net_amount' => 90,
        'paid_amount' => 30,
        'balance_amount' => 60,
        'status' => StudentDueItem::STATUS_PARTIALLY_PAID,
    ]);
    dueItem($student, $academicYear, $feeCategory, [
        'original_amount' => 50,
        'discount_amount' => 0,
        'net_amount' => 50,
        'paid_amount' => 50,
        'balance_amount' => 0,
        'status' => StudentDueItem::STATUS_PAID,
    ]);

    $summary = duesReport()['summary'];

    expect($summary['total_original_amount'])->toBe('150.00');
    expect($summary['total_discount_amount'])->toBe('10.00');
    expect($summary['total_net_amount'])->toBe('140.00');
    expect($summary['total_paid_amount'])->toBe('80.00');
    expect($summary['total_balance_amount'])->toBe('60.00');
    expect($summary['due_item_count'])->toBe(2);
});

it('counts unpaid partially paid and paid due items', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $student = reportStudent($academicYear, $grade, $section);
    $feeCategory = FeeCategory::factory()->create();

    dueItem($student, $academicYear, $feeCategory, ['status' => StudentDueItem::STATUS_UNPAID, 'balance_amount' => 100]);
    dueItem($student, $academicYear, $feeCategory, ['status' => StudentDueItem::STATUS_UNPAID, 'balance_amount' => 100]);
    dueItem($student, $academicYear, $feeCategory, [
        'status' => StudentDueItem::STATUS_PARTIALLY_PAID,
        'paid_amount' => 20,
        'balance_amount' => 80,
    ]);
    dueItem($student, $academicYear, $feeCategory, [
        'status' => StudentDueItem::STATUS_PAID,
        'paid_amount' => 100,
        'balance_amount' => 0,
    ]);

    $summary = duesReport()['summary'];

    expect($summary['unpaid_count'])->toBe(2);
    expect($summary['partially_paid_count'])->toBe(1);
    expect($summary['paid_count'])->toBe(1);
});

it('filters by academic year', function () {
    $academicYear = AcademicYear::factory()->create();
    $otherAcademicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $feeCategory = FeeCategory::factory()->create();

    dueItem(reportStudent($academicYear, $grade, $section), $academicYear, $feeCategory, [
        'net_amount' => 10,
        'balance_amount' => 10,
    ]);
    dueItem(reportStudent($otherAcademicYear, $grade, $section), $otherAcademicYear, $feeCategory, [
        'net_amount' => 999,
        'balance_amount' => 999,
    ]);

    $summary = duesReport(['academic_year_id' => $academicYear->id])['summary'];

    expect($summary['due_item_count'])->toBe(1);
    expect($summary['total_balance_amount'])->toBe('10.00');
});

it('filters by fee category', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $student = reportStudent($academicYear, $grade, $section);
    $tuition = FeeCategory::factory()->create();
    $transport = FeeCategory::factory()->create();

    dueItem($student, $academicYear, $tuition, ['net_amount' => 10, 'balance_amount' => 10]);
    dueItem($student, $academicYear, $transport, ['net_amount' => 25, 'balance_amount' => 25]);

    $summary = duesReport(['fee_category_id' => $transport->id])['summary'];

    expect($summary['due_item_count'])->toBe(1);
    expect($summary['total_balance_amount'])->toBe('25.00');
});

it('filters by family', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $feeCategory = FeeCategory::factory()->create();
    $family = Family::factory()->create();
    $otherFamily = Family::factory()->create();

    dueItem(reportStudent($academicYear, $grade, $section, $family), $academicYear, $feeCategory, [
        'net_amount' => 15,
        'balance_amount' => 15,
    ]);
    dueItem(reportStudent($academicYear, $grade, $section, $otherFamily), $academicYear, $feeCategory, [
        'net_amount' => 77,
        'balance_amount' => 77,
    ]);

    $summary = duesReport(['family_id' => $family->id])['summary'];

    expect($summary['due_item_count'])->toBe(1);
    expect($summary['total_balance_amount'])->toBe('15.00');
});

it('filters by due date range', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $student = reportStudent($academicYear, $grade, $section);
    $feeCategory = FeeCategory::factory()->create();

    dueItem($student, $academicYear, $feeCategory, ['due_date' => '2026-01-15', 'balance_amount' => 10]);
    dueItem($student, $academicYear, $feeCategory, ['due_date' => '2026-06-15', 'balance_amount' => 20]);
    dueItem($student, $academicYear, $feeCategory, ['due_date' => '2026-12-15', 'balance_amount' => 30]);

    $summary = duesReport([
        'due_date_from' => '2026-02-01',
        'due_date_to' => '2026-11-30',
    ])['summary'];

    expect($summary['due_item_count'])->toBe(1);
    expect($summary['total_balance_amount'])->toBe('20.00');
});

it('filters by grade through enrollment', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $otherGrade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $otherSection = Section::factory()->for($otherGrade)->create();
    $feeCategory = FeeCategory::factory()->create();

    dueItem(reportStudent($academicYear, $grade, $section), $academicYear, $feeCategory, ['balance_amount' => 11]);
    dueItem(reportStudent($academicYear, $otherGrade, $otherSection), $academicYear, $feeCategory, ['balance_amount' => 99]);

    $summary = duesReport([
        'academic_year_id' => $academicYear->id,
        'grade_id' => $grade->id,
    ])['summary'];

    expect($summary['due_item_count'])->toBe(1);
    expect($summary['total_balance_amount'])->toBe('11.00');
});

it('filters by section through enrollment', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $firstSection = Section::factory()->for($grade)->create();
    $secondSection = Section::factory()->for($grade)->create();
    $feeCategory = FeeCategory::factory()->create();

    dueItem(reportStudent($academicYear, $grade, $firstSection), $academicYear, $feeCategory, ['balance_amount' => 7]);
    dueItem(reportStudent($academicYear, $grade, $secondSection), $academicYear, $feeCategory, ['balance_amount' => 88]);

    $summary = duesReport([
        'academic_year_id' => $academicYear->id,
        'section_id' => $firstSection->id,
    ])['summary'];

    expect($summary['due_item_count'])->toBe(1);
    expect($summary['total_balance_amount'])->toBe('7.00');
});

it('throws when a grade filter is used without an academic year', function () {
    expect(fn () => duesReport(['grade_id' => Grade::factory()->create()->id]))
        ->toThrow(InvalidArgumentException::class);
});

it('throws when a section filter is used without an academic year', function () {
    expect(fn () => duesReport(['section_id' => Section::factory()->create()->id]))
        ->toThrow(InvalidArgumentException::class);
});

it('groups due items by family with balance descending', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $feeCategory = FeeCategory::factory()->create();
    $smallFamily = Family::factory()->create(['family_code' => 'FAM-SMALL']);
    $largeFamily = Family::factory()->create(['family_code' => 'FAM-LARGE']);

    dueItem(reportStudent($academicYear, $grade, $section, $smallFamily), $academicYear, $feeCategory, [
        'net_amount' => 10,
        'paid_amount' => 0,
        'balance_amount' => 10,
    ]);
    dueItem(reportStudent($academicYear, $grade, $section, $largeFamily), $academicYear, $feeCategory, [
        'net_amount' => 100,
        'paid_amount' => 20,
        'balance_amount' => 80,
    ]);

    $balances = duesReport()['family_balances'];

    expect($balances)->toHaveCount(2);
    expect($balances[0]['family_code'])->toBe('FAM-LARGE');
    expect($balances[0]['total_balance_amount'])->toBe('80.00');
    expect($balances[0]['total_paid_amount'])->toBe('20.00');
    expect($balances[1]['family_code'])->toBe('FAM-SMALL');
});

it('groups due items by student with balance descending', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $feeCategory = FeeCategory::factory()->create();

    $cheapStudent = reportStudent($academicYear, $grade, $section);
    $priceyStudent = reportStudent($academicYear, $grade, $section);

    dueItem($cheapStudent, $academicYear, $feeCategory, ['balance_amount' => 5]);
    dueItem($priceyStudent, $academicYear, $feeCategory, ['balance_amount' => 95]);

    $balances = duesReport()['student_balances'];

    expect($balances)->toHaveCount(2);
    expect($balances[0]['student_id'])->toBe($priceyStudent->id);
    expect($balances[0]['admission_no'])->toBe($priceyStudent->admission_no);
    expect($balances[0]['total_balance_amount'])->toBe('95.00');
    expect($balances[1]['student_id'])->toBe($cheapStudent->id);
});

it('excludes fully paid items from outstanding due items', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $student = reportStudent($academicYear, $grade, $section);
    $feeCategory = FeeCategory::factory()->create();

    dueItem($student, $academicYear, $feeCategory, [
        'due_date' => '2026-05-01',
        'net_amount' => 40,
        'balance_amount' => 40,
        'status' => StudentDueItem::STATUS_UNPAID,
    ]);
    dueItem($student, $academicYear, $feeCategory, [
        'due_date' => '2026-04-01',
        'net_amount' => 30,
        'paid_amount' => 30,
        'balance_amount' => 0,
        'status' => StudentDueItem::STATUS_PAID,
    ]);

    $outstanding = duesReport()['outstanding_due_items'];

    expect($outstanding)->toHaveCount(1);
    expect($outstanding[0]['net_amount'])->toBe('40.00');
    expect($outstanding[0]['balance_amount'])->toBe('40.00');
    expect($outstanding[0]['fee_category_name'])->toBe($feeCategory->name);
    expect($outstanding[0]['status'])->toBe(StudentDueItem::STATUS_UNPAID);
});

it('sorts outstanding due items by due date then id', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $student = reportStudent($academicYear, $grade, $section);
    $feeCategory = FeeCategory::factory()->create();

    dueItem($student, $academicYear, $feeCategory, ['due_date' => '2026-09-01', 'balance_amount' => 3]);
    dueItem($student, $academicYear, $feeCategory, ['due_date' => '2026-02-01', 'balance_amount' => 1]);
    dueItem($student, $academicYear, $feeCategory, ['due_date' => '2026-02-01', 'balance_amount' => 2]);

    $outstanding = duesReport()['outstanding_due_items'];

    expect($outstanding)->toHaveCount(3);
    expect(array_column($outstanding, 'balance_amount'))->toBe(['1.00', '2.00', '3.00']);
    expect($outstanding[0]['student_due_item_id'])->toBeLessThan($outstanding[1]['student_due_item_id']);
});

it('groups by fee category with totals', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $student = reportStudent($academicYear, $grade, $section);
    $tuition = FeeCategory::factory()->create(['name' => 'Tuition']);
    $transport = FeeCategory::factory()->create(['name' => 'Transport']);

    dueItem($student, $academicYear, $tuition, [
        'net_amount' => 100,
        'paid_amount' => 40,
        'balance_amount' => 60,
    ]);
    dueItem($student, $academicYear, $tuition, [
        'net_amount' => 50,
        'paid_amount' => 0,
        'balance_amount' => 50,
    ]);
    dueItem($student, $academicYear, $transport, [
        'net_amount' => 20,
        'paid_amount' => 20,
        'balance_amount' => 0,
    ]);

    $byCategory = duesReport()['by_fee_category'];

    expect($byCategory)->toHaveCount(2);
    expect($byCategory[0]['fee_category_name'])->toBe('Tuition');
    expect($byCategory[0]['total_net_amount'])->toBe('150.00');
    expect($byCategory[0]['total_paid_amount'])->toBe('40.00');
    expect($byCategory[0]['total_balance_amount'])->toBe('110.00');
    expect($byCategory[0]['due_item_count'])->toBe(2);
    expect($byCategory[1]['fee_category_name'])->toBe('Transport');
    expect($byCategory[1]['total_balance_amount'])->toBe('0.00');
});

it('returns zeroed summary when no due items match', function () {
    $summary = duesReport(['academic_year_id' => AcademicYear::factory()->create()->id])['summary'];

    expect($summary['total_net_amount'])->toBe('0.00');
    expect($summary['total_balance_amount'])->toBe('0.00');
    expect($summary['due_item_count'])->toBe(0);
    expect(duesReport()['family_balances'])->toBe([]);
    expect(duesReport()['student_balances'])->toBe([]);
    expect(duesReport()['outstanding_due_items'])->toBe([]);
});

it('does not create or modify any money records', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $student = reportStudent($academicYear, $grade, $section);
    $feeCategory = FeeCategory::factory()->create();

    dueItem($student, $academicYear, $feeCategory, [
        'net_amount' => 75,
        'paid_amount' => 25,
        'balance_amount' => 50,
        'status' => StudentDueItem::STATUS_PARTIALLY_PAID,
    ]);

    $dueItemCount = StudentDueItem::count();
    $paymentCount = Payment::count();
    $allocationCount = PaymentAllocation::count();
    $receiptCount = Receipt::count();
    $before = StudentDueItem::query()->first()->only([
        'original_amount',
        'discount_amount',
        'net_amount',
        'paid_amount',
        'balance_amount',
        'status',
    ]);

    duesReport();
    duesReport(['academic_year_id' => $academicYear->id, 'grade_id' => $grade->id]);

    expect(StudentDueItem::count())->toBe($dueItemCount);
    expect(Payment::count())->toBe($paymentCount);
    expect(PaymentAllocation::count())->toBe($allocationCount);
    expect(Receipt::count())->toBe($receiptCount);
    expect(StudentDueItem::query()->first()->only(array_keys($before)))->toBe($before);
});
