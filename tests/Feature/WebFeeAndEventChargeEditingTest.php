<?php

use App\Models\Event;
use App\Models\EventCharge;
use App\Models\EventDueItem;
use App\Models\FeeStructure;
use App\Models\Role;
use App\Models\StudentDueItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

it('lets an admin update a fee structure before it generates due items', function () {
    $feeStructure = FeeStructure::factory()->create([
        'amount' => '100.00',
        'frequency' => 'monthly',
    ]);
    $identity = $feeStructure->only(['fee_category_id', 'grade_id', 'academic_year_id']);

    $this->actingAs(adminUser())
        ->get(route('fee-structures.edit', $feeStructure))
        ->assertOk()
        ->assertSee('Update fee structure');

    $this->actingAs(adminUser())
        ->put(route('fee-structures.update', $feeStructure), [
            'amount' => '125.50',
            'frequency' => 'termly',
            'fee_category_id' => $feeStructure->fee_category_id + 100,
            'grade_id' => $feeStructure->grade_id + 100,
            'academic_year_id' => $feeStructure->academic_year_id + 100,
        ])
        ->assertRedirect(route('fee-structures.index'));

    $feeStructure->refresh();

    expect($feeStructure->amount)->toBe('125.50');
    expect($feeStructure->frequency)->toBe('termly');
    expect($feeStructure->only(['fee_category_id', 'grade_id', 'academic_year_id']))->toBe($identity);
});

it('rejects a fee structure update after a due item directly references it', function () {
    $feeStructure = FeeStructure::factory()->create(['amount' => '100.00', 'frequency' => 'monthly']);
    $dueItem = StudentDueItem::factory()->for($feeStructure, 'feeStructure')->create([
        'description' => 'Tuition',
        'frequency' => 'monthly',
        'original_amount' => '100.00',
        'discount_amount' => '10.00',
        'net_amount' => '90.00',
        'paid_amount' => '0.00',
        'balance_amount' => '90.00',
    ]);
    $snapshot = $dueItem->only(['description', 'frequency', 'original_amount', 'discount_amount', 'net_amount', 'paid_amount', 'balance_amount']);

    $this->actingAs(adminUser())
        ->put(route('fee-structures.update', $feeStructure), [
            'amount' => '150.00',
            'frequency' => 'yearly',
        ])
        ->assertSessionHasErrors('fee_structure');

    expect($feeStructure->refresh()->only(['amount', 'frequency']))->toBe([
        'amount' => '100.00',
        'frequency' => 'monthly',
    ]);
    expect($dueItem->refresh()->only(array_keys($snapshot)))->toBe($snapshot);
});

it('rejects a fee structure update that duplicates its fixed identity', function () {
    $feeStructure = FeeStructure::factory()->create(['frequency' => 'monthly']);
    $amount = $feeStructure->amount;
    FeeStructure::factory()->create([
        'fee_category_id' => $feeStructure->fee_category_id,
        'grade_id' => $feeStructure->grade_id,
        'academic_year_id' => $feeStructure->academic_year_id,
        'frequency' => 'termly',
    ]);

    $this->actingAs(adminUser())
        ->put(route('fee-structures.update', $feeStructure), [
            'amount' => '150.00',
            'frequency' => 'termly',
        ])
        ->assertSessionHasErrors('frequency');

    expect($feeStructure->refresh()->only(['amount', 'frequency']))->toBe([
        'amount' => $amount,
        'frequency' => 'monthly',
    ]);
});

it('lets an admin update an event charge before the event generates due items', function () {
    $event = Event::factory()->create();
    $charge = EventCharge::factory()->for($event)->create(['amount' => '25.00']);
    $gradeId = $charge->grade_id;

    $this->actingAs(adminUser())
        ->get(route('events.charges.edit', [$event, $charge]))
        ->assertOk()
        ->assertSee('Update event charge');

    $this->actingAs(adminUser())
        ->put(route('events.charges.update', [$event, $charge]), [
            'amount' => '30.50',
            'grade_id' => $gradeId + 100,
        ])
        ->assertRedirect(route('events.show', $event));

    expect($charge->refresh()->amount)->toBe('30.50');
    expect($charge->grade_id)->toBe($gradeId);
});

it('rejects every event charge update after any event due item exists', function () {
    $event = Event::factory()->create();
    EventCharge::factory()->for($event)->create();
    $charge = EventCharge::factory()->for($event)->create(['amount' => '25.00']);
    $dueItem = StudentDueItem::factory()->create([
        'description' => 'Event: Sports Day',
        'frequency' => null,
        'original_amount' => '40.00',
        'discount_amount' => '5.00',
        'net_amount' => '35.00',
        'paid_amount' => '0.00',
        'balance_amount' => '35.00',
    ]);
    EventDueItem::factory()->for($event)->for($dueItem)->create();
    $snapshot = $dueItem->only(['description', 'frequency', 'original_amount', 'discount_amount', 'net_amount', 'paid_amount', 'balance_amount']);

    $this->actingAs(adminUser())
        ->put(route('events.charges.update', [$event, $charge]), ['amount' => '30.00'])
        ->assertSessionHasErrors('event_charge');

    expect($charge->refresh()->amount)->toBe('25.00');
    expect($dueItem->refresh()->only(array_keys($snapshot)))->toBe($snapshot);
});

it('denies guests the fee structure and event charge edit pages', function () {
    $feeStructure = FeeStructure::factory()->create();
    $event = Event::factory()->create();
    $charge = EventCharge::factory()->for($event)->create();

    $this->get(route('fee-structures.edit', $feeStructure))->assertRedirect(route('login'));
    $this->get(route('events.charges.edit', [$event, $charge]))->assertRedirect(route('login'));
    $this->put(route('fee-structures.update', $feeStructure), ['amount' => '150.00', 'frequency' => 'yearly'])
        ->assertRedirect(route('login'));
    $this->put(route('events.charges.update', [$event, $charge]), ['amount' => '30.00'])
        ->assertRedirect(route('login'));
});

it('denies teachers and accountants fee structure and event charge updates', function (string $role) {
    $feeStructure = FeeStructure::factory()->create();
    $event = Event::factory()->create();
    $charge = EventCharge::factory()->for($event)->create();
    $user = userWithRole($role);

    $this->actingAs($user)
        ->put(route('fee-structures.update', $feeStructure), ['amount' => '150.00', 'frequency' => 'yearly'])
        ->assertForbidden();
    $this->actingAs($user)
        ->put(route('events.charges.update', [$event, $charge]), ['amount' => '30.00'])
        ->assertForbidden();
})->with([
    'teacher' => Role::TEACHER,
    'accountant' => Role::ACCOUNTANT,
]);

it('scopes event charge routes and exposes no delete routes', function () {
    $feeStructure = FeeStructure::factory()->create();
    $event = Event::factory()->create();
    $otherEvent = Event::factory()->create();
    $charge = EventCharge::factory()->for($otherEvent)->create();

    $this->actingAs(adminUser())
        ->get(route('events.charges.edit', [$event, $charge]))
        ->assertNotFound();

    expect(Route::has('fee-structures.destroy'))->toBeFalse();
    expect(Route::has('events.charges.destroy'))->toBeFalse();

    $this->actingAs(adminUser())
        ->delete(route('fee-structures.update', $feeStructure))
        ->assertMethodNotAllowed();
    $this->actingAs(adminUser())
        ->delete(route('events.charges.update', [$otherEvent, $charge]))
        ->assertMethodNotAllowed();
});
