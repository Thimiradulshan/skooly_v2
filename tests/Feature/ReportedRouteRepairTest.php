<?php

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\SchoolSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('renders each reported create page for an authorized admin', function (string $routeName, string $view) {
    setActiveAcademicYear(AcademicYear::factory()->create());

    $this->actingAs(adminUser())->get(route($routeName))
        ->assertOk()
        ->assertViewIs($view);
})->with([
    'fee structure' => ['fee-structures.create', 'fee-structures.create'],
    'section year assignment' => ['section-year-assignments.create', 'section-year-assignments.create'],
    'teacher assignment' => ['teacher-assignments.create', 'teacher-assignments.create'],
]);

it('renders school settings for an authorized admin when configured', function () {
    setActiveAcademicYear(AcademicYear::factory()->create());

    $this->actingAs(adminUser())->get(route('school-settings.edit'))
        ->assertOk()
        ->assertViewIs('school-settings.edit');
});

it('provides first-run school settings setup instead of a 404', function () {
    $academicYear = AcademicYear::factory()->create(['name' => '2026/2027']);

    $this->actingAs(adminUser())->get(route('school-settings.edit'))
        ->assertOk()
        ->assertViewIs('school-settings.edit')
        ->assertSee('Initial setup is incomplete')
        ->assertSee('2026/2027');

    $this->actingAs(adminUser())->put(route('school-settings.update'), [
        'active_academic_year_id' => $academicYear->id,
    ])->assertRedirect(route('school-settings.edit'));

    expect(SchoolSetting::query()->sole()->active_academic_year_id)->toBe($academicYear->id);
});

it('redirects active-year create pages to setup when school settings are missing', function (string $routeName) {
    AcademicYear::factory()->create();

    $this->actingAs(adminUser())->get(route($routeName))
        ->assertRedirect(route('school-settings.edit'))
        ->assertSessionHas('error');
})->with([
    'fee structure' => ['fee-structures.create'],
    'section year assignment' => ['section-year-assignments.create'],
    'teacher assignment' => ['teacher-assignments.create'],
    'term' => ['terms.create'],
    'event' => ['events.create'],
    'recurring due generation' => ['due-generation.recurring.create'],
    'payment reminders' => ['payment-reminders.create'],
    'promotion batch' => ['promotion-batches.create'],
]);

it('keeps guests out of every reported route', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with([
    'fee structure' => ['fee-structures.create'],
    'school settings' => ['school-settings.edit'],
    'section year assignment' => ['section-year-assignments.create'],
    'teacher assignment' => ['teacher-assignments.create'],
]);

it('keeps unauthorized roles out of every reported route', function (string $role, string $routeName) {
    $this->actingAs(userWithRole($role))->get(route($routeName))->assertForbidden();
})->with([
    'teacher fee structure' => [Role::TEACHER, 'fee-structures.create'],
    'teacher school settings' => [Role::TEACHER, 'school-settings.edit'],
    'teacher section year assignment' => [Role::TEACHER, 'section-year-assignments.create'],
    'teacher teaching assignment' => [Role::TEACHER, 'teacher-assignments.create'],
    'accountant fee structure' => [Role::ACCOUNTANT, 'fee-structures.create'],
    'accountant school settings' => [Role::ACCOUNTANT, 'school-settings.edit'],
    'accountant section year assignment' => [Role::ACCOUNTANT, 'section-year-assignments.create'],
    'accountant teaching assignment' => [Role::ACCOUNTANT, 'teacher-assignments.create'],
]);

it('resolves the four reported named routes to their expected urls', function () {
    expect(route('fee-structures.create', absolute: false))->toBe('/fee-structures/create')
        ->and(route('school-settings.edit', absolute: false))->toBe('/school-settings')
        ->and(route('section-year-assignments.create', absolute: false))->toBe('/section-year-assignments/create')
        ->and(route('teacher-assignments.create', absolute: false))->toBe('/teacher-assignments/create');
});
