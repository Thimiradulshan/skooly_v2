<?php

use App\Models\StudentDueItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('rejects negative student due item monetary values and inconsistent balances', function (array $attributes) {
    expect(fn () => StudentDueItem::factory()->create($attributes))->toThrow(QueryException::class);
})->with([
    'negative original amount' => [['original_amount' => -0.01]],
    'negative discount amount' => [['discount_amount' => -0.01]],
    'negative net amount' => [['net_amount' => -0.01]],
    'negative paid amount' => [['paid_amount' => -0.01]],
    'negative balance amount' => [['balance_amount' => -0.01]],
    'paid and balance do not equal net' => [['paid_amount' => 1, 'balance_amount' => 98]],
]);
