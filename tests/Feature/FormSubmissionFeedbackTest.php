<?php

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\FeeCategory;
use App\Models\Role;
use App\Models\Term;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function formFeedbackEventPayload(AcademicYear $academicYear, FeeCategory $feeCategory, array $overrides = []): array
{
    return array_merge([
        'academic_year_id' => $academicYear->id,
        'fee_category_id' => $feeCategory->id,
        'name' => 'Sports Day',
        'event_date' => '2026-10-15',
        'description' => 'Annual sports event',
        'is_mandatory' => '1',
    ], $overrides);
}

it('renders success feedback as a visible accessible alert without JavaScript', function () {
    $this->withSession(['status' => 'Family created.'])
        ->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Family created.')
        ->assertSee('role="status"', false)
        ->assertSee('aria-live="polite"', false)
        ->assertSee('class="alert alert-success"', false);
});

it('renders error feedback as a visible accessible alert without JavaScript', function () {
    $this->withSession(['error' => 'Something went wrong.'])
        ->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Something went wrong.')
        ->assertSee('role="alert"', false)
        ->assertSee('class="alert alert-error"', false);
});

it('creates an event, redirects, and shows visible success feedback', function () {
    $admin = adminUser();
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);
    $feeCategory = FeeCategory::factory()->create();

    $this->actingAs($admin)
        ->post(route('events.store'), formFeedbackEventPayload($academicYear, $feeCategory))
        ->assertRedirect()
        ->assertSessionHas('status', 'Event created.');

    $event = Event::query()->sole();
    expect($event->name)->toBe('Sports Day');

    $this->actingAs($admin)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Event created.')
        ->assertSee('role="status"', false)
        ->assertSee('data-flash-message="Event created."', false);
});

it('keeps event input and shows validation feedback when creation fails', function () {
    $admin = adminUser();
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);
    $feeCategory = FeeCategory::factory()->create();

    $response = $this->actingAs($admin)
        ->from(route('events.create'))
        ->followingRedirects()
        ->post(route('events.store'), formFeedbackEventPayload($academicYear, $feeCategory, [
            'name' => '',
            'event_date' => 'not-a-date',
            'description' => 'Keep this description',
        ]));

    $response->assertOk()
        ->assertSee('Please correct the following:')
        ->assertSee('The name field is required.')
        ->assertSee('The event date field must be a valid date.')
        ->assertSee('Keep this description');

    expect(Event::query()->count())->toBe(0);
});

it('denies event creation to users without the admin role without changing data', function () {
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);
    $feeCategory = FeeCategory::factory()->create();

    $this->actingAs(userWithRole(Role::TEACHER))
        ->post(route('events.store'), formFeedbackEventPayload($academicYear, $feeCategory))
        ->assertForbidden();

    expect(Event::query()->count())->toBe(0);
});

it('archives and restores a term with success feedback while preserving history', function () {
    $admin = adminUser();
    $term = Term::factory()->create();

    $this->actingAs($admin)
        ->get(route('terms.show', $term))
        ->assertOk()
        ->assertSee('data-confirm="Archive this term? Existing history will remain available."', false)
        ->assertSee('data-loading', false);

    $this->actingAs($admin)
        ->post(route('terms.archive', $term))
        ->assertRedirect(route('terms.show', $term))
        ->assertSessionHas('status', 'Term archived. Existing history remains available.');

    expect($term->refresh()->is_archived)->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'action' => AuditLog::ACTION_TERM_ARCHIVED,
        'auditable_type' => $term->getMorphClass(),
        'auditable_id' => $term->id,
    ]);

    $this->actingAs($admin)
        ->get(route('terms.show', $term))
        ->assertOk()
        ->assertSee('Term archived. Existing history remains available.')
        ->assertSee('role="status"', false)
        ->assertSee('Restore term');

    $this->actingAs($admin)
        ->post(route('terms.restore', $term))
        ->assertRedirect(route('terms.show', $term))
        ->assertSessionHas('status', 'Term restored.');

    expect($term->refresh()->is_archived)->toBeFalse();

    $this->assertDatabaseHas('audit_logs', [
        'action' => AuditLog::ACTION_TERM_RESTORED,
        'auditable_type' => $term->getMorphClass(),
        'auditable_id' => $term->id,
    ]);
});

it('denies term archive and restore to non-admin users without changing state', function () {
    $term = Term::factory()->create();

    $this->actingAs(userWithRole(Role::TEACHER))
        ->post(route('terms.archive', $term))
        ->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))
        ->post(route('terms.archive', $term))
        ->assertForbidden();

    expect($term->refresh()->is_archived)->toBeFalse();

    $archived = Term::factory()->create(['is_archived' => true]);

    $this->actingAs(userWithRole(Role::TEACHER))
        ->post(route('terms.restore', $archived))
        ->assertForbidden();

    expect($archived->refresh()->is_archived)->toBeTrue();
});

it('updates an event with success feedback and no duplicate record', function () {
    $admin = adminUser();
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);
    $feeCategory = FeeCategory::factory()->create();
    $event = Event::factory()->create([
        'academic_year_id' => $academicYear->id,
        'fee_category_id' => $feeCategory->id,
        'name' => 'Old name',
    ]);

    $this->actingAs($admin)
        ->put(route('events.update', $event), formFeedbackEventPayload($academicYear, $feeCategory, [
            'name' => 'New name',
        ]))
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHas('status', 'Event updated.');

    expect(Event::query()->count())->toBe(1);
    expect($event->refresh()->name)->toBe('New name');
});
