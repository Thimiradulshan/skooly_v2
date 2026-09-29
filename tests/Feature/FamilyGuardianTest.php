<?php

use App\Models\Family;
use App\Models\Guardian;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

it('allows a family to have multiple guardians', function () {
    $family = Family::factory()->create();
    Guardian::factory()->count(2)->recycle($family)->create();

    expect($family->guardians)->toHaveCount(2);
});

it('associates each guardian with one family', function () {
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();

    expect($guardian->family->is($family))->toBeTrue();
});

it('enables combined billing by default', function () {
    $family = Family::factory()->create()->refresh();

    expect($family->combined_billing_enabled)->toBeTrue();
});

it('restricts deleting a family with guardians', function () {
    $family = Family::factory()->create();
    Guardian::factory()->for($family)->create();

    expect(fn () => $family->delete())->toThrow(QueryException::class);

    $this->assertModelExists($family);
});

it('does not create the guardian student table in Phase 3', function () {
    expect(Schema::hasTable('guardian_student'))->toBeFalse();
});

it('does not add a student relationship to guardians in Phase 3', function () {
    expect(method_exists(Guardian::class, 'students'))->toBeFalse();
});
