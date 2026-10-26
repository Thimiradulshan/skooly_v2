<?php

use App\Models\Family;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('shows the latest recorded payments with their real receipt data', function () {
    $family = Family::factory()->create(['family_code' => 'FAM-9001']);
    $payment = Payment::factory()->for($family)->create([
        'payment_reference' => 'PAY-9001',
        'amount' => '321.45',
        'paid_at' => now()->subDay(),
    ]);
    Receipt::factory()->for($payment)->create(['receipt_no' => 'RCT-9001']);

    $response = $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Recent transactions')
        ->assertSee('FAM-9001')
        ->assertSee('PAY-9001')
        ->assertSee('RCT-9001')
        ->assertSee('321.45')
        ->assertSee(route('payments.show', $payment, false), false)
        ->assertSee(route('receipts.show', $payment->receipt, false), false)
        ->assertSee(route('payments.collect', [], false), false);

    $response->assertViewHas(
        'recentPayments',
        fn ($payments): bool => $payments->count() === 1 && $payments->first()->is($payment),
    );
});

it('shows an actionable empty state when no payments exist', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Recent transactions')
        ->assertSee('No transactions yet')
        ->assertSee('Collect payment');
});

it('limits the dashboard table to the six most recent payments', function () {
    $payments = Payment::factory()->count(8)->create([
        'paid_at' => now()->subDays(2),
    ]);
    $payments->first()->update(['paid_at' => now()]);

    $response = $this->actingAs(adminUser())->get(route('admin.dashboard'))->assertOk();

    $response->assertViewHas('recentPayments', function ($recentPayments) use ($payments): bool {
        return $recentPayments->count() === 6
            && $recentPayments->first()->is($payments->first());
    });
});
