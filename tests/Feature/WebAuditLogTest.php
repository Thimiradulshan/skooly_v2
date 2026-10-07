<?php

use App\Models\AuditLog;
use App\Models\Family;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

it('blocks guests from the audit log', function () {
    $this->get(route('audit-logs.index'))->assertRedirect(route('login'));
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

it('has no audit log mutation routes', function () {
    expect(Route::has('audit-logs.create'))->toBeFalse();
    expect(Route::has('audit-logs.store'))->toBeFalse();
    expect(Route::has('audit-logs.edit'))->toBeFalse();
    expect(Route::has('audit-logs.update'))->toBeFalse();
    expect(Route::has('audit-logs.destroy'))->toBeFalse();

    $auditLog = AuditLog::factory()->create();

    $this->actingAs(adminUser())
        ->delete(route('audit-logs.show', $auditLog))
        ->assertMethodNotAllowed();

    expect(User::query()->count())->toBeGreaterThan(0);
});
