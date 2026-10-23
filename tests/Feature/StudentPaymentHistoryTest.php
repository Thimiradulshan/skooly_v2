<?php

use App\Models\Family;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('does not include another students payment allocations in the students payment history', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create(['name' => 'Visible student']);
    $otherStudent = Student::factory()->for($family)->create(['name' => 'Other student']);
    $visibleDueItem = StudentDueItem::factory()->for($student)->create(['description' => 'Visible due item']);
    $otherDueItem = StudentDueItem::factory()->for($otherStudent)->create(['description' => 'Other due item']);

    Payment::recordManual($family, 'RCT-VISIBLE-STUDENT', 'cash', '100.00', [
        ['student_due_item_id' => $visibleDueItem->id, 'amount' => '100.00'],
    ]);
    Payment::recordManual($family, 'RCT-OTHER-STUDENT', 'cash', '100.00', [
        ['student_due_item_id' => $otherDueItem->id, 'amount' => '100.00'],
    ]);

    $this->actingAs(adminUser())
        ->get(route('students.payment-history', $student))
        ->assertOk()
        ->assertSee('Visible due item')
        ->assertSee('RCT-VISIBLE-STUDENT')
        ->assertDontSee('Other due item')
        ->assertDontSee('RCT-OTHER-STUDENT');
});
