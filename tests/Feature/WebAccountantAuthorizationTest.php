<?php

use App\Models\Family;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentReminder;
use App\Models\PaymentReversal;
use App\Models\Receipt;
use App\Models\Role;
use App\Models\Student;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('lets an accountant use approved finance pages and payment collection without family access', function () {
    $accountant = userWithRole(Role::ACCOUNTANT);
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $guardian = Guardian::factory()->for($family)->create();
    $payment = Payment::factory()->for($family)->create();
    $receipt = Receipt::factory()->for($payment)->create();
    $reversal = PaymentReversal::factory()->for($payment, 'originalPayment')->create();
    $reminder = PaymentReminder::factory()->for($family)->for($guardian)->create();

    $this->actingAs($accountant)->get(route('payments.index'))->assertOk()
        ->assertSee('Collect Payment')
        ->assertSee('Dues Dashboard')
        ->assertSee('Payment Reminders')
        ->assertDontSee('Families & Students')
        ->assertDontSee('Academic Years')
        ->assertDontSee('Events')
        ->assertDontSee('Promotion');
    $this->actingAs($accountant)->get(route('payments.show', $payment))->assertOk();
    $this->actingAs($accountant)->get(route('receipts.index'))->assertOk();
    $this->actingAs($accountant)->get(route('receipts.show', $receipt))->assertOk();
    $this->actingAs($accountant)->get(route('payment-reversals.index'))->assertOk();
    $this->actingAs($accountant)->get(route('payment-reversals.show', $reversal))->assertOk();
    $this->actingAs($accountant)->get(route('dues-dashboard.index'))->assertOk();
    $this->actingAs($accountant)->get(route('payment-reminders.index'))->assertOk();
    $this->actingAs($accountant)->get(route('payment-reminders.show', $reminder))->assertOk();

    $this->actingAs($accountant)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($accountant)->get(route('families.index'))->assertForbidden();
    $this->actingAs($accountant)->get(route('families.show', $family))->assertForbidden();
    $this->actingAs($accountant)->get(route('students.show', $student))->assertForbidden();
    $this->actingAs($accountant)->get(route('guardians.show', $guardian))->assertForbidden();
    $this->actingAs($accountant)->get(route('academic-years.index'))->assertForbidden();
    $this->actingAs($accountant)->get(route('users.index'))->assertForbidden();
    $this->actingAs($accountant)->get(route('events.index'))->assertForbidden();
    $this->actingAs($accountant)->get(route('promotion-batches.index'))->assertForbidden();
    $this->actingAs($accountant)->get(route('payments.collect'))->assertOk();
    $this->actingAs($accountant)
        ->get(route('payments.collect', ['family_code' => $family->family_code]))
        ->assertRedirect(route('families.payments.create', $family));
    $this->actingAs($accountant)->get(route('families.payments.create', $family))->assertOk();
    $this->actingAs($accountant)->get(route('due-generation.recurring.create'))->assertForbidden();
    $this->actingAs($accountant)->get(route('payment-reminders.create'))->assertForbidden();
    $this->actingAs($accountant)->post(route('payment-reminders.store'), [])->assertForbidden();
    $this->actingAs($accountant)->post(route('payment-reminders.cancel', $reminder))->assertForbidden();
    $this->actingAs($accountant)->post(route('payment-reversals.approve', $reversal), [])->assertForbidden();
});
