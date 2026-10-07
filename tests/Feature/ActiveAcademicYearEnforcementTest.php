<?php

use App\Actions\Fees\CreateFeeStructure;
use App\Actions\Fees\GenerateRecurringDueItems;
use App\Actions\Promotion\CreatePromotionBatch;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\Grade;
use App\Models\SchoolSetting;
use App\Models\Section;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use InvalidArgumentException;

uses(LazilyRefreshDatabase::class);

function activeAcademicYear(): AcademicYear
{
    $academicYear = AcademicYear::factory()->create();
    SchoolSetting::factory()->for($academicYear, 'activeAcademicYear')->create();

    return $academicYear;
}

it('defaults the recurring dues form to the active year and excludes non-active years', function () {
    $active = activeAcademicYear();
    $inactive = AcademicYear::factory()->create();

    $this->actingAs(adminUser())->get(route('due-generation.recurring.create'))
        ->assertOk()
        ->assertSee('value="'.$active->id.'" selected', false)
        ->assertDontSee('value="'.$inactive->id.'"', false);

});

it('rejects non-active fee configuration and generation at the action boundary', function () {
    activeAcademicYear();
    $inactive = AcademicYear::factory()->create();
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => true]);
    $grade = Grade::factory()->create();

    expect(fn () => app(CreateFeeStructure::class)->handle($feeCategory, $grade, $inactive, '100.00', 'monthly'))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => app(GenerateRecurringDueItems::class)->handle($inactive, '2026-10-01'))
        ->toThrow(InvalidArgumentException::class);
});

it('accepts a historical promotion source only when the target is active', function () {
    $active = activeAcademicYear();
    $source = AcademicYear::factory()->create(['is_archived' => true]);
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $section = Section::factory()->for($grade)->create(['is_archived' => true]);

    $batch = app(CreatePromotionBatch::class)->handle($source, $active, [$section->id]);

    expect($batch->source_academic_year_id)->toBe($source->id);
    expect($batch->target_academic_year_id)->toBe($active->id);
});

it('rejects a non-active promotion target at the action boundary', function () {
    $source = activeAcademicYear();
    $inactiveTarget = AcademicYear::factory()->create();
    $section = Section::factory()->for(Grade::factory())->create();

    expect(fn () => app(CreatePromotionBatch::class)->handle($source, $inactiveTarget, [$section->id]))
        ->toThrow(InvalidArgumentException::class);
});
