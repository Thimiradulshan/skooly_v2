<?php

use App\Actions\Promotion\CreatePromotionBatch;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\PromotionBatch;
use App\Models\PromotionBatchItem;
use App\Models\Role;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

function promotedWebStudent(AcademicYear $academicYear, Grade $grade, Section $section): Student
{
    $student = Student::factory()->create(['status' => Student::STATUS_ACTIVE]);

    Enrollment::factory()->for($student)->for($academicYear)->for($grade)->for($section)->create();

    return $student;
}

function promotionWebPayload(AcademicYear $source, AcademicYear $target, Section $section): array
{
    return [
        'source_academic_year_id' => $source->id,
        'target_academic_year_id' => $target->id,
        'source_section_ids' => [$section->id],
    ];
}

it('denies guests the promotion batch index', function () {
    $this->get(route('promotion-batches.index'))->assertRedirect(route('login'));
});

it('denies teachers and accountants the promotion batch index', function () {
    $this->actingAs(userWithRole(Role::TEACHER))->get(route('promotion-batches.index'))->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->get(route('promotion-batches.index'))->assertForbidden();
});

it('lets an admin view the promotion batch index and create page', function () {
    $this->actingAs(adminUser())->get(route('promotion-batches.index'))->assertOk();
    $this->actingAs(adminUser())->get(route('promotion-batches.create'))->assertOk();
});

it('lets an admin create a draft promotion batch through the existing action', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2]);
    Section::factory()->for($nextGrade)->create(['name' => 'A']);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    $student = promotedWebStudent($source, $grade, $section);

    $this->actingAs(adminUser())
        ->post(route('promotion-batches.store'), promotionWebPayload($source, $target, $section))
        ->assertRedirect();

    $batch = PromotionBatch::query()->with('items')->sole();

    expect($batch->status)->toBe(PromotionBatch::STATUS_DRAFT);
    expect($batch->items)->toHaveCount(1);
    expect($batch->items->sole()->student_id)->toBe($student->id);
    expect($batch->items->sole()->action)->toBe(PromotionBatchItem::ACTION_PROMOTE);
    expect(AuditLog::where('action', AuditLog::ACTION_PROMOTION_BATCH_CREATED)->count())->toBe(1);
});

it('shows a promotion batch and its items', function () {
    $source = AcademicYear::factory()->create(['name' => '2025/2026']);
    $target = AcademicYear::factory()->create(['name' => '2026/2027']);
    $grade = Grade::factory()->create(['sequence_order' => 1, 'name' => 'Grade 1']);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2, 'name' => 'Grade 2']);
    Section::factory()->for($nextGrade)->create(['name' => 'A']);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    $student = promotedWebStudent($source, $grade, $section);
    $batch = app(CreatePromotionBatch::class)->handle($source, $target, [$section->id]);

    $this->actingAs(adminUser())
        ->get(route('promotion-batches.show', $batch))
        ->assertOk()
        ->assertSee('2025/2026')
        ->assertSee('2026/2027')
        ->assertSee($student->name)
        ->assertSee('Confirm promotion batch');
});

it('confirms a promotion batch and creates target year enrollment without modifying source enrollment', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2]);
    $targetSection = Section::factory()->for($nextGrade)->create(['name' => 'A']);
    $sourceSection = Section::factory()->for($grade)->create(['name' => 'A']);
    $student = promotedWebStudent($source, $grade, $sourceSection);
    $sourceEnrollment = Enrollment::query()->sole();
    $before = $sourceEnrollment->only(['student_id', 'academic_year_id', 'grade_id', 'section_id']);

    $this->actingAs(adminUser())
        ->post(route('promotion-batches.store'), promotionWebPayload($source, $target, $sourceSection));

    $batch = PromotionBatch::query()->sole();

    $this->actingAs(adminUser())
        ->post(route('promotion-batches.confirm', $batch))
        ->assertRedirect(route('promotion-batches.show', $batch));

    $targetEnrollment = Enrollment::query()->where('academic_year_id', $target->id)->sole();

    expect($targetEnrollment->student_id)->toBe($student->id);
    expect($targetEnrollment->grade_id)->toBe($nextGrade->id);
    expect($targetEnrollment->section_id)->toBe($targetSection->id);
    expect($sourceEnrollment->refresh()->only(array_keys($before)))->toBe($before);
    expect($batch->refresh()->status)->toBe(PromotionBatch::STATUS_CONFIRMED);
    expect($batch->confirmed_at)->not->toBeNull();
    expect(AuditLog::where('action', AuditLog::ACTION_PROMOTION_BATCH_CONFIRMED)->count())->toBe(1);
    expect(StudentDueItem::count())->toBe(0);
});

it('supports the graduated student path through the existing action', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $topGrade = Grade::factory()->create(['sequence_order' => 99]);
    $section = Section::factory()->for($topGrade)->create();
    $student = promotedWebStudent($source, $topGrade, $section);

    $this->actingAs(adminUser())
        ->post(route('promotion-batches.store'), promotionWebPayload($source, $target, $section));

    $batch = PromotionBatch::query()->sole();

    $this->actingAs(adminUser())->post(route('promotion-batches.confirm', $batch))->assertRedirect();

    expect($student->refresh()->status)->toBe(Student::STATUS_GRADUATED);
    expect(Enrollment::where('academic_year_id', $target->id)->count())->toBe(0);
    expect($batch->items()->sole()->action)->toBe(PromotionBatchItem::ACTION_GRADUATE);
    expect($batch->items()->sole()->status)->toBe(PromotionBatchItem::STATUS_APPLIED);
    expect(StudentDueItem::count())->toBe(0);
});

it('rejects a second confirmation without duplicating enrollments', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2]);
    Section::factory()->for($nextGrade)->create(['name' => 'A']);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    promotedWebStudent($source, $grade, $section);
    $batch = app(CreatePromotionBatch::class)->handle($source, $target, [$section->id]);

    $this->actingAs(adminUser())->post(route('promotion-batches.confirm', $batch))->assertRedirect();
    $this->actingAs(adminUser())
        ->post(route('promotion-batches.confirm', $batch))
        ->assertSessionHasErrors('promotion_batch');

    expect(Enrollment::where('academic_year_id', $target->id)->count())->toBe(1);
});

it('lets an admin discard a draft promotion batch without changing students or enrollments', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $section = Section::factory()->for($grade)->create();
    $student = promotedWebStudent($source, $grade, $section);
    $batch = app(CreatePromotionBatch::class)->handle($source, $target, [$section->id]);

    $this->actingAs(adminUser())
        ->post(route('promotion-batches.discard', $batch))
        ->assertRedirect(route('promotion-batches.show', $batch));

    expect($batch->refresh()->status)->toBe(PromotionBatch::STATUS_DISCARDED);
    expect($batch->discarded_at)->not->toBeNull();
    expect($student->refresh()->status)->toBe(Student::STATUS_ACTIVE);
    expect(Enrollment::where('academic_year_id', $target->id)->count())->toBe(0);
});

it('does not discard a confirmed promotion batch', function () {
    $batch = PromotionBatch::factory()->create([
        'status' => PromotionBatch::STATUS_CONFIRMED,
        'confirmed_at' => now(),
    ]);

    $this->actingAs(adminUser())
        ->post(route('promotion-batches.discard', $batch))
        ->assertSessionHasErrors('promotion_batch');

    expect($batch->refresh()->status)->toBe(PromotionBatch::STATUS_CONFIRMED);
});

it('adds no promotion reversal or destructive route', function () {
    expect(Route::has('promotion-batches.destroy'))->toBeFalse();
    expect(Route::has('promotion-batches.reverse'))->toBeFalse();

    $batch = PromotionBatch::factory()->create();

    $this->actingAs(adminUser())
        ->delete(route('promotion-batches.show', $batch))
        ->assertMethodNotAllowed();
});
