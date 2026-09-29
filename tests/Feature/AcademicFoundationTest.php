<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchoolSetting;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

it('uses the school setting academic year as the active academic year', function () {
    $academicYear = AcademicYear::factory()->create([
        'name' => '2026/2027',
        'start_date' => '2026-09-01',
        'end_date' => '2027-06-30',
    ]);
    $setting = SchoolSetting::factory()->for($academicYear, 'activeAcademicYear')->create();

    expect($setting->activeAcademicYear->is($academicYear))->toBeTrue();
    expect($academicYear->activeSchoolSetting->is($setting))->toBeTrue();
    expect($academicYear->start_date)->toBeInstanceOf(Carbon::class);
});

it('uses the fixed singleton school settings ID', function () {
    $setting = SchoolSetting::factory()->create();

    expect($setting->id)->toBe(1);
    expect(fn () => SchoolSetting::factory()->create())->toThrow(QueryException::class);
});

it('scopes terms to an academic year', function () {
    $academicYear = AcademicYear::factory()->create();
    $term = Term::factory()->for($academicYear)->create([
        'name' => 'First Term',
        'start_date' => '2026-09-01',
        'end_date' => '2026-12-15',
    ]);

    expect($term->academicYear->is($academicYear))->toBeTrue();
    expect($academicYear->terms)->toHaveCount(1);
    expect($term->start_date)->toBeInstanceOf(Carbon::class);
});

it('prevents duplicate term names within an academic year', function () {
    $academicYear = AcademicYear::factory()->create();
    Term::factory()->for($academicYear)->create(['name' => 'First Term']);

    expect(fn () => Term::factory()->for($academicYear)->create(['name' => 'First Term']))
        ->toThrow(QueryException::class);
});

it('orders grades by a unique sequence order for later promotion', function () {
    $grade = Grade::factory()->create([
        'name' => 'Grade 1',
        'sequence_order' => 1,
    ]);

    expect($grade->sequence_order)->toBeInt()->toBe(1);
    expect(fn () => Grade::factory()->create([
        'name' => 'Grade 2',
        'sequence_order' => 1,
    ]))->toThrow(QueryException::class);
});

it('keeps sections attached to persistent grades instead of academic years', function () {
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create([
        'name' => 'A',
        'capacity' => 30,
    ]);

    expect($section->grade->is($grade))->toBeTrue();
    expect($grade->sections->sole()->is($section))->toBeTrue();
    expect($section->capacity)->toBeInt()->toBe(30);
    expect(Schema::hasColumn('sections', 'academic_year_id'))->toBeFalse();
});

it('allows the same section name in different grades but not twice in one grade', function () {
    $firstGrade = Grade::factory()->create();
    $secondGrade = Grade::factory()->create();
    Section::factory()->for($firstGrade)->create(['name' => 'A']);
    Section::factory()->for($secondGrade)->create(['name' => 'A']);

    expect(fn () => Section::factory()->for($firstGrade)->create(['name' => 'A']))
        ->toThrow(QueryException::class);
});
