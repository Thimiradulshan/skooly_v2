<?php

use App\Models\AuditLog;
use App\Models\Family;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

it('blocks guests from the audit log', function () {
    $this->get(route('audit-logs.index'))->assertRedirect(route('login'));
    $this->get(route('audit-logs.export.csv'))->assertRedirect(route('login'));
    $this->get(route('audit-logs.export.pdf'))->assertRedirect(route('login'));
});

it('blocks teachers and accountants from the audit log', function () {
    $this->actingAs(userWithRole(Role::TEACHER))->get(route('audit-logs.index'))->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->get(route('audit-logs.index'))->assertForbidden();
});

it('lists audit logs newest first with their stored subjects and actors', function () {
    $actor = adminUser();
    $actor->update(['name' => 'Audit Admin']);
    $family = Family::factory()->create();
    AuditLog::factory()->for($actor, 'actor')->for($family, 'auditable')->create([
        'action' => AuditLog::ACTION_FAMILY_CREATED,
        'occurred_at' => '2026-10-01 09:00:00',
    ]);
    AuditLog::factory()->for($actor, 'actor')->for($family, 'auditable')->create([
        'action' => AuditLog::ACTION_FAMILY_UPDATED,
        'occurred_at' => '2026-10-01 10:00:00',
    ]);

    $this->actingAs(adminUser())
        ->get(route('audit-logs.index'))
        ->assertOk()
        ->assertSeeInOrder(['Family Updated', 'Family Created'])
        ->assertSee('Audit Admin')
        ->assertSee('Family #'.$family->id)
        ->assertSee('Audit Log');
});

it('shows stored audit metadata and remains readable without an actor', function () {
    $family = Family::factory()->create();
    $auditLog = AuditLog::factory()->for($family, 'auditable')->create([
        'action' => AuditLog::ACTION_PAYMENT_RECORDED,
        'metadata' => ['payment_id' => 42, 'amount' => '100.00'],
    ]);

    $this->actingAs(adminUser())
        ->get(route('audit-logs.show', $auditLog))
        ->assertOk()
        ->assertSee('Payment Recorded')
        ->assertSee('System')
        ->assertSee('payment_id')
        ->assertSee('100.00');
});

it('filters audit logs by stored action', function () {
    AuditLog::factory()->create(['action' => AuditLog::ACTION_FAMILY_CREATED]);
    AuditLog::factory()->create(['action' => AuditLog::ACTION_PAYMENT_RECORDED]);

    $this->actingAs(adminUser())
        ->get(route('audit-logs.index', ['action' => AuditLog::ACTION_PAYMENT_RECORDED]))
        ->assertOk()
        ->assertViewHas('auditLogs', fn ($auditLogs) => $auditLogs->total() === 1
            && $auditLogs->first()->action === AuditLog::ACTION_PAYMENT_RECORDED);
});

it('filters audit logs by stored actor', function () {
    $matchingActor = User::factory()->create(['name' => 'Matching actor']);
    $otherActor = User::factory()->create(['name' => 'Other actor']);
    AuditLog::factory()->for($matchingActor, 'actor')->create();
    AuditLog::factory()->for($otherActor, 'actor')->create();

    $this->actingAs(adminUser())
        ->get(route('audit-logs.index', ['actor_user_id' => $matchingActor->id]))
        ->assertOk()
        ->assertViewHas('auditLogs', fn ($auditLogs) => $auditLogs->total() === 1
            && $auditLogs->first()->actor_user_id === $matchingActor->id);
});

it('filters audit logs by stored record type', function () {
    AuditLog::factory()->create(['auditable_type' => Family::class, 'auditable_id' => 111]);
    AuditLog::factory()->create(['auditable_type' => Payment::class, 'auditable_id' => 222]);

    $this->actingAs(adminUser())
        ->get(route('audit-logs.index', ['auditable_type' => Payment::class]))
        ->assertOk()
        ->assertViewHas('auditLogs', fn ($auditLogs) => $auditLogs->total() === 1
            && $auditLogs->first()->auditable_type === Payment::class
            && $auditLogs->first()->auditable_id === 222);
});

it('filters audit logs by occurred at date range', function () {
    AuditLog::factory()->create(['occurred_at' => '2026-10-01 23:59:59']);
    AuditLog::factory()->create(['occurred_at' => '2026-10-02 12:00:00']);
    AuditLog::factory()->create(['occurred_at' => '2026-10-03 00:00:00']);

    $this->actingAs(adminUser())
        ->get(route('audit-logs.index', [
            'occurred_at_from' => '2026-10-02',
            'occurred_at_to' => '2026-10-02',
        ]))
        ->assertOk()
        ->assertViewHas('auditLogs', fn ($auditLogs) => $auditLogs->total() === 1
            && $auditLogs->first()->occurred_at->toDateTimeString() === '2026-10-02 12:00:00');
});

it('rejects filters that are not stored audit values or an invalid date range', function () {
    AuditLog::factory()->create();

    $this->actingAs(adminUser())
        ->get(route('audit-logs.index', [
            'action' => 'unknown_action',
            'actor_user_id' => 999999,
            'auditable_type' => 'App\\Models\\UnknownRecord',
            'occurred_at_from' => '2026-10-03',
            'occurred_at_to' => '2026-10-02',
        ]))
        ->assertInvalid(['action', 'actor_user_id', 'auditable_type', 'occurred_at_to']);
});

it('combines filters and preserves them when paginating audit logs', function () {
    $actor = User::factory()->create();
    $family = Family::factory()->create();

    AuditLog::factory()->count(21)->for($actor, 'actor')->for($family, 'auditable')->create([
        'action' => AuditLog::ACTION_FAMILY_CREATED,
        'occurred_at' => '2026-10-02 12:00:00',
    ]);
    AuditLog::factory()->create(['action' => AuditLog::ACTION_PAYMENT_RECORDED, 'occurred_at' => '2026-10-02 12:00:00']);

    $this->actingAs(adminUser())
        ->get(route('audit-logs.index', [
            'action' => AuditLog::ACTION_FAMILY_CREATED,
            'actor_user_id' => $actor->id,
            'auditable_type' => Family::class,
            'occurred_at_from' => '2026-10-02',
            'occurred_at_to' => '2026-10-02',
            'page' => 2,
        ]))
        ->assertOk()
        ->assertViewHas('auditLogs', fn ($auditLogs) => $auditLogs->currentPage() === 2
            && $auditLogs->count() === 1
            && str_contains((string) $auditLogs->previousPageUrl(), 'action=family_created')
            && str_contains((string) $auditLogs->previousPageUrl(), 'actor_user_id='.$actor->id)
            && str_contains((string) $auditLogs->previousPageUrl(), 'auditable_type='.urlencode(Family::class))
            && str_contains((string) $auditLogs->previousPageUrl(), 'occurred_at_from=2026-10-02'));
});

it('exports filtered audit logs as a streamed CSV with JSON metadata and filter summary', function () {
    $family = Family::factory()->create();
    AuditLog::factory()->for($family, 'auditable')->create([
        'action' => AuditLog::ACTION_FAMILY_CREATED,
        'metadata' => ['reference' => '=unsafe', 'amount' => '100.00'],
    ]);
    AuditLog::factory()->create(['action' => AuditLog::ACTION_PAYMENT_RECORDED]);

    $response = $this->actingAs(adminUser())->get(route('audit-logs.export.csv', [
        'action' => AuditLog::ACTION_FAMILY_CREATED,
    ]));

    $response
        ->assertOk()
        ->assertDownload('audit-logs.csv')
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    expect($response->streamedContent())
        ->toContain('Filters')
        ->toContain('family_created')
        ->toContain('reference')
        ->toContain('=unsafe')
        ->not->toContain('payment_recorded');
});

it('exports filtered audit logs as a downloadable PDF', function () {
    AuditLog::factory()->create(['action' => AuditLog::ACTION_FAMILY_CREATED]);

    $response = $this->actingAs(adminUser())->get(route('audit-logs.export.pdf', [
        'action' => AuditLog::ACTION_FAMILY_CREATED,
    ]));

    $response
        ->assertOk()
        ->assertDownload('audit-logs.pdf')
        ->assertHeader('Content-Type', 'application/pdf');

    expect($response->getContent())->toStartWith('%PDF');
});

it('blocks teachers and accountants from audit log exports', function () {
    foreach ([Role::TEACHER, Role::ACCOUNTANT] as $role) {
        $this->actingAs(userWithRole($role))->get(route('audit-logs.export.csv'))->assertForbidden();
        $this->actingAs(userWithRole($role))->get(route('audit-logs.export.pdf'))->assertForbidden();
    }
});

it('has no audit log mutation routes', function () {
    expect(Route::has('audit-logs.create'))->toBeFalse();
    expect(Route::has('audit-logs.store'))->toBeFalse();
    expect(Route::has('audit-logs.edit'))->toBeFalse();
    expect(Route::has('audit-logs.update'))->toBeFalse();
    expect(Route::has('audit-logs.destroy'))->toBeFalse();
    expect(Route::has('audit-logs.archive'))->toBeFalse();
    expect(Route::has('audit-logs.purge'))->toBeFalse();

    $auditLog = AuditLog::factory()->create();

    $this->actingAs(adminUser())
        ->delete(route('audit-logs.show', $auditLog))
        ->assertMethodNotAllowed();

    expect(User::query()->count())->toBeGreaterThan(0);
});
