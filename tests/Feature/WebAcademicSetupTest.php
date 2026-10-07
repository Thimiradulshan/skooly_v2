<?php

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Grade;
use App\Models\Role;
use App\Models\SchoolSetting;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

it('blocks guests from the academic years index', function () {
    $this->get(route('academic-years.index'))->assertRedirect(route('login'));
});

it('blocks teachers and accountants from academic setup', function () {
    $this->actingAs(userWithRole(Role::TEACHER))->get(route('academic-years.index'))->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->get(route('academic-years.index'))->assertForbidden();
});

it('lets an admin access the academic years index', function () {
    AcademicYear::factory()->create(['name' => '2026/2027']);

    $this->actingAs(adminUser())->get(route('academic-years.index'))->assertOk()->assertSee('2026/2027');
});

it('lets an admin create view and update an academic year', function () {
    $admin = adminUser();
    $this->actingAs($admin)->post(route('academic-years.store'), [
        'name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30',
    ])->assertRedirect();
    $academicYear = AcademicYear::query()->sole();
    $this->actingAs($admin)->get(route('academic-years.show', $academicYear))->assertOk()->assertSee('2026/2027');
    $this->actingAs($admin)->put(route('academic-years.update', $academicYear), [
        'name' => '2027/2028', 'start_date' => '2027-09-01', 'end_date' => '2028-06-30',
    ])->assertRedirect(route('academic-years.show', $academicYear));
    expect($academicYear->refresh()->name)->toBe('2027/2028');
});

it('lets an admin create view and update a term', function () {
    $admin = adminUser();
    $academicYear = AcademicYear::factory()->create();
    $this->actingAs($admin)->post(route('terms.store'), [
        'academic_year_id' => $academicYear->id, 'name' => 'First Term', 'start_date' => '2026-09-01', 'end_date' => '2026-12-15',
    ])->assertRedirect();
    $term = Term::query()->sole();
    $this->actingAs($admin)->get(route('terms.show', $term))->assertOk()->assertSee('First Term');
    $this->actingAs($admin)->put(route('terms.update', $term), [
        'academic_year_id' => $academicYear->id, 'name' => 'Autumn Term', 'start_date' => '2026-09-01', 'end_date' => '2026-12-20',
    ])->assertRedirect(route('terms.show', $term));
    expect($term->refresh()->name)->toBe('Autumn Term');
});

it('lets an admin create view and update a grade', function () {
    $admin = adminUser();
    $this->actingAs($admin)->post(route('grades.store'), ['name' => 'Grade 1', 'sequence_order' => 1])->assertRedirect();
    $grade = Grade::query()->sole();
    $this->actingAs($admin)->get(route('grades.show', $grade))->assertOk()->assertSee('Grade 1');
    $this->actingAs($admin)->put(route('grades.update', $grade), ['name' => 'Year 1', 'sequence_order' => 1])->assertRedirect(route('grades.show', $grade));
    expect($grade->refresh()->name)->toBe('Year 1');
});

it('lets an admin create view and update a section', function () {
    $admin = adminUser();
    $grade = Grade::factory()->create();
    $this->actingAs($admin)->post(route('sections.store'), ['grade_id' => $grade->id, 'name' => 'A', 'capacity' => 30])->assertRedirect();
    $section = Section::query()->sole();
    $this->actingAs($admin)->get(route('sections.show', $section))->assertOk()->assertSee('A');
    $this->actingAs($admin)->put(route('sections.update', $section), ['grade_id' => $grade->id, 'name' => 'B', 'capacity' => 32])->assertRedirect(route('sections.show', $section));
    expect($section->refresh()->only(['name', 'capacity']))->toBe(['name' => 'B', 'capacity' => 32]);
});

it('lets an admin update the active academic year setting', function () {
    $admin = adminUser();
    $firstYear = AcademicYear::factory()->create();
    $secondYear = AcademicYear::factory()->create();
    $setting = SchoolSetting::factory()->for($firstYear, 'activeAcademicYear')->create();
    $this->actingAs($admin)->put(route('school-settings.update'), ['active_academic_year_id' => $secondYear->id])->assertRedirect(route('school-settings.edit'));
    expect($setting->refresh()->activeAcademicYear->is($secondYear))->toBeTrue();
});

it('shows academic setup navigation and dashboard links', function () {
    SchoolSetting::factory()->create();
    $response = $this->actingAs(adminUser())->get(route('admin.dashboard'));
    $response->assertOk()->assertSee('Academic Setup')->assertSee('Academic Years')->assertSee('School Settings')->assertSee('Academic setup');
});

it('has no delete routes for academic setup', function () {
    expect(Route::has('academic-years.destroy'))->toBeFalse();
    expect(Route::has('terms.destroy'))->toBeFalse();
    expect(Route::has('grades.destroy'))->toBeFalse();
    expect(Route::has('sections.destroy'))->toBeFalse();
});

it('archives and restores academic setup records without deleting history', function (string $model, string $archiveRoute, string $restoreRoute, string $archiveAction, string $restoreAction) {
    $admin = adminUser();
    $record = $model::factory()->create();

    $this->actingAs($admin)->post(route($archiveRoute, $record))->assertRedirect();

    expect($record->refresh()->is_archived)->toBeTrue();
    $this->assertDatabaseHas('audit_logs', [
        'action' => $archiveAction,
        'auditable_type' => $record->getMorphClass(),
        'auditable_id' => $record->id,
        'actor_user_id' => $admin->id,
    ]);

    $this->actingAs($admin)->post(route($restoreRoute, $record))->assertRedirect();

    expect($record->refresh()->is_archived)->toBeFalse();
    $this->assertDatabaseHas('audit_logs', [
        'action' => $restoreAction,
        'auditable_type' => $record->getMorphClass(),
        'auditable_id' => $record->id,
        'actor_user_id' => $admin->id,
    ]);
})->with([
    [AcademicYear::class, 'academic-years.archive', 'academic-years.restore', AuditLog::ACTION_ACADEMIC_YEAR_ARCHIVED, AuditLog::ACTION_ACADEMIC_YEAR_RESTORED],
    [Term::class, 'terms.archive', 'terms.restore', AuditLog::ACTION_TERM_ARCHIVED, AuditLog::ACTION_TERM_RESTORED],
    [Grade::class, 'grades.archive', 'grades.restore', AuditLog::ACTION_GRADE_ARCHIVED, AuditLog::ACTION_GRADE_RESTORED],
    [Section::class, 'sections.archive', 'sections.restore', AuditLog::ACTION_SECTION_ARCHIVED, AuditLog::ACTION_SECTION_RESTORED],
]);

it('keeps archived academic setup records visible in indexes and details', function () {
    $academicYear = AcademicYear::factory()->create(['is_archived' => true, 'name' => 'Archived year']);
    $term = Term::factory()->for($academicYear)->create(['is_archived' => true, 'name' => 'Archived term']);

    $this->actingAs(adminUser())->get(route('academic-years.index'))->assertOk()->assertSee('Archived year');
    $this->actingAs(adminUser())->get(route('terms.show', $term))->assertOk()->assertSee('Archived term')->assertSee('Archived');
});

it('prevents archiving the active academic year and excludes archived years from school settings', function () {
    $admin = adminUser();
    $activeYear = AcademicYear::factory()->create(['name' => 'Active year']);
    $archivedYear = AcademicYear::factory()->create(['is_archived' => true, 'name' => 'Archived year']);
    SchoolSetting::factory()->for($activeYear, 'activeAcademicYear')->create();

    $this->actingAs($admin)->post(route('academic-years.archive', $activeYear))
        ->assertSessionHasErrors('academic_year');
    expect($activeYear->refresh()->is_archived)->toBeFalse();

    $this->actingAs($admin)->get(route('school-settings.edit'))
        ->assertSee('Active year')
        ->assertDontSee('Archived year');
    $this->actingAs($admin)->put(route('school-settings.update'), ['active_academic_year_id' => $archivedYear->id])
        ->assertSessionHasErrors('active_academic_year_id');
});

it('excludes archived records from new configuration selectors', function () {
    $academicYear = AcademicYear::factory()->create(['is_archived' => true, 'name' => 'Archived year']);
    $grade = Grade::factory()->create(['is_archived' => true, 'name' => 'Archived grade']);
    $section = Section::factory()->for($grade)->create(['is_archived' => true, 'name' => 'Archived section']);

    $this->actingAs(adminUser())->get(route('terms.create'))->assertDontSee('Archived year');
    $this->actingAs(adminUser())->get(route('sections.create'))->assertDontSee('Archived grade');
    $this->actingAs(adminUser())->get(route('promotion-batches.create'))->assertDontSee('Archived year')->assertDontSee('Archived section');
});
