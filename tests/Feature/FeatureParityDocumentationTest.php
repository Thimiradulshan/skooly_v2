<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function parityDoc(string $name): string
{
    $path = base_path('docs/'.$name);

    expect(file_exists($path))->toBeTrue();

    return (string) file_get_contents($path);
}

it('ships all four parity audit documents', function () {
    parityDoc('backend-frontend-feature-parity.md');
    parityDoc('crud-coverage-matrix.md');
    parityDoc('frontend-missing-feature-backlog.md');
    parityDoc('destructive-action-policy-draft.md');
});

it('documents the academic foundation modules that have no web UI', function () {
    $parity = parityDoc('backend-frontend-feature-parity.md');

    foreach (['Academic Year', 'Term', 'Grade', 'Section'] as $module) {
        expect($parity)->toContain($module);
    }

    $backlog = parityDoc('frontend-missing-feature-backlog.md');

    expect($backlog)->toContain('Academic Year');
    expect($backlog)->toContain('Grade');
    expect($backlog)->toContain('Section');
});

it('states that archive and delete need business decisions', function () {
    $matrix = parityDoc('crud-coverage-matrix.md');
    $policy = parityDoc('destructive-action-policy-draft.md');

    expect($matrix)->toContain('Needs Decision');
    expect($matrix)->toContain('Not Recommended');
    expect($policy)->toContain('Needs Decision');
    expect($policy)->toContain('business approval');
});

it('states that payments and receipts must never be hard deleted', function () {
    $policy = parityDoc('destructive-action-policy-draft.md');

    expect($policy)->toContain('never be hard deleted');
    expect($policy)->toContain('payments');
    expect($policy)->toContain('receipts');
    expect($policy)->toContain('audit_logs');
    expect($policy)->toContain('Correction is not deletion');
});

it('records the payment correction path as an open decision', function () {
    $policy = parityDoc('destructive-action-policy-draft.md');
    $backlog = parityDoc('frontend-missing-feature-backlog.md');

    expect($policy)->toContain('refund');
    expect($backlog)->toContain('refund');
});

it('documents audit log viewing and its remaining gaps', function () {
    $parity = parityDoc('backend-frontend-feature-parity.md');
    $backlog = parityDoc('frontend-missing-feature-backlog.md');

    expect($parity)->toContain('Audit Logs');
    expect($parity)->toContain('AuditLogController');
    expect($backlog)->toContain('Audit log filtering, export, and retention');
});

it('does not claim any delete route currently exists', function () {
    expect(Route::has('families.destroy'))->toBeFalse();
    expect(Route::has('payments.destroy'))->toBeFalse();
    expect(Route::has('receipts.destroy'))->toBeFalse();
    expect(Route::has('promotion-batches.destroy'))->toBeFalse();
    expect(Route::has('academic-years.destroy'))->toBeFalse();
});
