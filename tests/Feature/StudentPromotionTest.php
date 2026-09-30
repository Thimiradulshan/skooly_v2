<?php

use App\Actions\Promotion\ConfirmPromotionBatch;
use App\Actions\Promotion\CreatePromotionBatch;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\PromotionBatch;
use App\Models\PromotionBatchItem;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use InvalidArgumentException;
use RuntimeException;

uses(LazilyRefreshDatabase::class);

function activeStudentIn(AcademicYear $academicYear, Grade $grade, Section $section, string $status = Student::STATUS_ACTIVE): Student
{
    $student = Student::factory()->create(['status' => $status]);

    Enrollment::factory()
        ->for($student)
        ->for($academicYear)
        ->for($grade)
        ->for($section)
        ->create();

    return $student;
}

function makeBatch(AcademicYear $source, AcademicYear $target, array $sectionIds)
{
    return app(CreatePromotionBatch::class)->handle($source, $target, $sectionIds);
}

it('creates a draft promotion batch for selected source sections', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();

    $batch = makeBatch($source, $target, [$section->id]);

    expect($batch->status)->toBe(PromotionBatch::STATUS_DRAFT);
    expect($batch->source_academic_year_id)->toBe($source->id);
    expect($batch->target_academic_year_id)->toBe($target->id);
    expect($batch->confirmed_at)->toBeNull();
    expect($batch->sections)->toHaveCount(1);
    expect($batch->sections->sole()->source_section_id)->toBe($section->id);
});

it('includes only active students from the selected sections and source year', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $otherSource = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $otherSection = Section::factory()->for($grade)->create();
    $active = activeStudentIn($source, $grade, $section);
    activeStudentIn($source, $grade, $section, Student::STATUS_INACTIVE);
    activeStudentIn($source, $grade, $otherSection);
    activeStudentIn($otherSource, $grade, $section);

    $batch = makeBatch($source, $target, [$section->id]);

    expect($batch->items)->toHaveCount(1);
    expect($batch->items->sole()->student_id)->toBe($active->id);
});

it('does not create target year enrollments while the batch is a draft', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $nextGrade = Grade::factory()->create(['sequence_order' => $grade->sequence_order + 1]);
    Section::factory()->for($nextGrade)->create(['name' => 'A']);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    activeStudentIn($source, $grade, $section);

    makeBatch($source, $target, [$section->id]);

    expect(Enrollment::where('academic_year_id', $target->id)->count())->toBe(0);
});

it('defaults the target grade to the next grade by sequence order', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2]);
    $thirdGrade = Grade::factory()->create(['sequence_order' => 3]);
    Section::factory()->for($nextGrade)->create(['name' => 'A']);
    Section::factory()->for($thirdGrade)->create(['name' => 'A']);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    activeStudentIn($source, $grade, $section);

    $item = makeBatch($source, $target, [$section->id])->items->sole();

    expect($item->action)->toBe(PromotionBatchItem::ACTION_PROMOTE);
    expect($item->target_grade_id)->toBe($nextGrade->id);
    expect($item->target_section_id)->not->toBeNull();
    expect($item->targetSection->grade_id)->toBe($nextGrade->id);
    expect($item->targetSection->name)->toBe('A');
});

it('defaults the action to graduate when no next grade exists', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $topGrade = Grade::factory()->create(['sequence_order' => 99]);
    $section = Section::factory()->for($topGrade)->create();
    activeStudentIn($source, $topGrade, $section);

    $item = makeBatch($source, $target, [$section->id])->items->sole();

    expect($item->action)->toBe(PromotionBatchItem::ACTION_GRADUATE);
    expect($item->target_grade_id)->toBeNull();
});

it('skips an excluded item without creating a target enrollment', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $nextGrade = Grade::factory()->create(['sequence_order' => $grade->sequence_order + 1]);
    Section::factory()->for($nextGrade)->create(['name' => 'A']);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    $student = activeStudentIn($source, $grade, $section);
    $item = makeBatch($source, $target, [$section->id])->items->sole();
    $item->update(['action' => PromotionBatchItem::ACTION_EXCLUDE]);

    app(ConfirmPromotionBatch::class)->handle($item->promotionBatch);

    expect($item->refresh()->status)->toBe(PromotionBatchItem::STATUS_SKIPPED);
    expect(Enrollment::where('academic_year_id', $target->id)->count())->toBe(0);
    expect($student->refresh()->status)->toBe(Student::STATUS_ACTIVE);
});

it('marks a graduated student and creates no target enrollment', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $student = activeStudentIn($source, $grade, $section);
    $batch = makeBatch($source, $target, [$section->id]);
    $item = $batch->items->sole();
    $item->update(['action' => PromotionBatchItem::ACTION_GRADUATE]);

    app(ConfirmPromotionBatch::class)->handle($batch);

    expect($item->refresh()->status)->toBe(PromotionBatchItem::STATUS_APPLIED);
    expect($student->refresh()->status)->toBe(Student::STATUS_GRADUATED);
    expect(Enrollment::where('academic_year_id', $target->id)->count())->toBe(0);
});

it('creates a target year enrollment for a promoted student', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2]);
    $targetSection = Section::factory()->for($nextGrade)->create(['name' => 'B']);
    $section = Section::factory()->for($grade)->create(['name' => 'B']);
    $student = activeStudentIn($source, $grade, $section);
    $batch = makeBatch($source, $target, [$section->id]);

    app(ConfirmPromotionBatch::class)->handle($batch);

    $created = Enrollment::where('academic_year_id', $target->id)->sole();

    expect($created->student_id)->toBe($student->id);
    expect($created->grade_id)->toBe($nextGrade->id);
    expect($created->section_id)->toBe($targetSection->id);
    expect($batch->refresh()->items->sole()->status)->toBe(PromotionBatchItem::STATUS_APPLIED);
    expect($batch->items->sole()->applied_enrollment_id)->toBe($created->id);
});

it('creates a target year enrollment in the current grade for a retained student', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    $student = activeStudentIn($source, $grade, $section);
    $batch = makeBatch($source, $target, [$section->id]);
    $batch->items->sole()->update([
        'action' => PromotionBatchItem::ACTION_RETAIN,
        'target_grade_id' => $grade->id,
        'target_section_id' => $section->id,
    ]);

    app(ConfirmPromotionBatch::class)->handle($batch);

    $created = Enrollment::where('academic_year_id', $target->id)->sole();

    expect($created->grade_id)->toBe($grade->id);
    expect($created->section_id)->toBe($section->id);
    expect($created->student_id)->toBe($student->id);
});

it('does not modify the source year enrollment on confirmation', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2]);
    Section::factory()->for($nextGrade)->create(['name' => 'A']);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    $student = activeStudentIn($source, $grade, $section);
    $sourceEnrollment = Enrollment::where('student_id', $student->id)->where('academic_year_id', $source->id)->sole();
    $before = $sourceEnrollment->only(['student_id', 'academic_year_id', 'grade_id', 'section_id']);
    $batch = makeBatch($source, $target, [$section->id]);

    app(ConfirmPromotionBatch::class)->handle($batch);

    expect($sourceEnrollment->refresh()->only(array_keys($before)))->toBe($before);
});

it('does not create student due items on confirmation', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2]);
    Section::factory()->for($nextGrade)->create(['name' => 'A']);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    activeStudentIn($source, $grade, $section);
    $batch = makeBatch($source, $target, [$section->id]);
    $dueItemCount = StudentDueItem::count();

    app(ConfirmPromotionBatch::class)->handle($batch);

    expect(StudentDueItem::count())->toBe($dueItemCount);
    expect(StudentDueItem::where('academic_year_id', $target->id)->count())->toBe(0);
});

it('marks the batch confirmed with applied and skipped items', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2]);
    Section::factory()->for($nextGrade)->create(['name' => 'A']);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    activeStudentIn($source, $grade, $section);
    activeStudentIn($source, $grade, $section);
    $batch = makeBatch($source, $target, [$section->id]);
    $batch->items()->orderBy('id')->first()->update(['action' => PromotionBatchItem::ACTION_EXCLUDE]);

    app(ConfirmPromotionBatch::class)->handle($batch);

    expect($batch->refresh()->status)->toBe(PromotionBatch::STATUS_CONFIRMED);
    expect($batch->confirmed_at)->not->toBeNull();
    expect($batch->items()->where('status', PromotionBatchItem::STATUS_APPLIED)->count())->toBe(1);
    expect($batch->items()->where('status', PromotionBatchItem::STATUS_SKIPPED)->count())->toBe(1);
});

it('rejects confirmation of a batch that is not a draft', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    activeStudentIn($source, $grade, $section);
    $batch = makeBatch($source, $target, [$section->id]);
    $batch->update(['status' => PromotionBatch::STATUS_CONFIRMED]);

    expect(fn () => app(ConfirmPromotionBatch::class)->handle($batch))
        ->toThrow(RuntimeException::class);
});

it('fails when a promote item has no target section', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2]);
    $section = Section::factory()->for($grade)->create();
    activeStudentIn($source, $grade, $section);
    $batch = makeBatch($source, $target, [$section->id]);
    $batch->items->sole()->update(['target_section_id' => null]);

    expect(fn () => app(ConfirmPromotionBatch::class)->handle($batch))
        ->toThrow(InvalidArgumentException::class);

    expect($batch->refresh()->status)->toBe(PromotionBatch::STATUS_DRAFT);
});

it('fails when the target section does not belong to the target grade', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2]);
    $wrongSection = Section::factory()->for($grade)->create();
    $section = Section::factory()->for($grade)->create();
    activeStudentIn($source, $grade, $section);
    $batch = makeBatch($source, $target, [$section->id]);
    $batch->items->sole()->update([
        'target_grade_id' => $nextGrade->id,
        'target_section_id' => $wrongSection->id,
    ]);

    expect(fn () => app(ConfirmPromotionBatch::class)->handle($batch))
        ->toThrow(InvalidArgumentException::class);

    expect(Enrollment::where('academic_year_id', $target->id)->count())->toBe(0);
});

it('rolls back every change when one item in the batch is invalid', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2]);
    $targetSection = Section::factory()->for($nextGrade)->create();
    $section = Section::factory()->for($grade)->create();
    $validStudent = activeStudentIn($source, $grade, $section);
    $invalidStudent = activeStudentIn($source, $grade, $section);
    $batch = makeBatch($source, $target, [$section->id]);
    $items = $batch->items()->orderBy('id')->get();
    $items[0]->update([
        'target_grade_id' => $nextGrade->id,
        'target_section_id' => $targetSection->id,
    ]);
    $items[1]->update(['target_grade_id' => $nextGrade->id, 'target_section_id' => null]);

    expect(fn () => app(ConfirmPromotionBatch::class)->handle($batch))
        ->toThrow(InvalidArgumentException::class);

    expect(Enrollment::where('academic_year_id', $target->id)->count())->toBe(0);
    expect($batch->refresh()->status)->toBe(PromotionBatch::STATUS_DRAFT);
    expect($batch->confirmed_at)->toBeNull();
    expect($items[0]->refresh()->status)->toBe(PromotionBatchItem::STATUS_PENDING);
    expect($items[0]->applied_enrollment_id)->toBeNull();
    expect($validStudent->refresh()->status)->toBe(Student::STATUS_ACTIVE);
    expect($invalidStudent->refresh()->status)->toBe(Student::STATUS_ACTIVE);
});

it('prevents duplicate items for the same student in one batch', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $student = activeStudentIn($source, $grade, $section);
    $batch = makeBatch($source, $target, [$section->id]);
    $item = $batch->items->sole();

    expect(fn () => $batch->items()->create([
        'student_id' => $student->id,
        'source_enrollment_id' => $item->source_enrollment_id,
        'source_section_id' => $section->id,
        'action' => PromotionBatchItem::ACTION_PROMOTE,
    ]))->toThrow(QueryException::class);
});
