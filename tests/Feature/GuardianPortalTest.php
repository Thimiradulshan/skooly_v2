<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDueItem;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('denies guests and unlinked accounts guardian portal access', function () {
    $this->get(route('guardian.portal'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create(['email_verified_at' => now()]))->get(route('guardian.portal'))->assertForbidden();
});

it('denies deactivated guardian accounts portal access', function () {
    $guardian = Guardian::factory()->for(Family::factory())->create();
    $user = User::factory()->create([
        'guardian_id' => $guardian->id,
        'email_verified_at' => now(),
        'is_active' => false,
    ]);

    $this->actingAs($user)->get(route('guardian.portal'))->assertForbidden();
});

it('shows academic profile only for explicitly linked students', function () {
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();
    $linked = Student::factory()->for($family)->create(['name' => 'Linked Learner']);
    $sibling = Student::factory()->for($family)->create(['name' => 'Unlinked Sibling']);
    $guardian->students()->attach($linked);
    $year = AcademicYear::factory()->create(['name' => '2026']);
    $grade = Grade::factory()->create(['name' => 'Grade 5']);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    Enrollment::factory()->for($linked)->for($year)->for($grade)->for($section)->create();
    $user = User::factory()->create(['guardian_id' => $guardian->id, 'email_verified_at' => now(), 'is_active' => true]);

    $this->actingAs($user)->get(route('guardian.students.show', $linked))
        ->assertOk()->assertSee('Grade 5')->assertSee('2026');
    $this->actingAs($user)->get(route('guardian.students.show', $sibling))->assertNotFound();
});

it('shows only allocation-level payment history and due items for linked students', function () {
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();
    $visible = Student::factory()->for($family)->create(['name' => 'Visible Fee Student']);
    $hidden = Student::factory()->for($family)->create(['name' => 'Hidden Fee Student']);
    $guardian->students()->attach($visible);
    $visibleDue = StudentDueItem::factory()->for($visible)->create(['description' => 'Visible tuition', 'net_amount' => 10, 'paid_amount' => 5, 'balance_amount' => 5]);
    $hiddenDue = StudentDueItem::factory()->for($hidden)->create(['description' => 'Hidden tuition', 'net_amount' => 10, 'paid_amount' => 5, 'balance_amount' => 5]);
    $payment = Payment::factory()->for($family)->create();
    PaymentAllocation::factory()->for($payment)->for($visibleDue)->create(['amount' => 5]);
    PaymentAllocation::factory()->for($payment)->for($hiddenDue)->create(['amount' => 5]);
    $user = User::factory()->create(['guardian_id' => $guardian->id, 'email_verified_at' => now(), 'is_active' => true]);

    $this->actingAs($user)->get(route('guardian.dues'))->assertOk()->assertSee('Visible tuition')->assertDontSee('Hidden tuition');
    $this->actingAs($user)->get(route('guardian.payments'))->assertOk()->assertSee('Visible Fee Student')->assertDontSee('Hidden Fee Student');
});

it('limits academic-year filters to years containing authorized dues', function () {
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();
    $student = Student::factory()->for($family)->create();
    $guardian->students()->attach($student);
    $allowedYear = AcademicYear::factory()->create(['name' => 'Allowed Year']);
    $otherYear = AcademicYear::factory()->create(['name' => 'Other Year']);
    StudentDueItem::factory()->for($student)->for($allowedYear)->create(['description' => 'Allowed due', 'net_amount' => 10, 'balance_amount' => 10]);
    $user = User::factory()->create(['guardian_id' => $guardian->id, 'email_verified_at' => now(), 'is_active' => true]);

    $this->actingAs($user)->get(route('guardian.dues'))
        ->assertOk()->assertSee('Allowed Year')->assertDontSee('Other Year');
    $this->actingAs($user)->get(route('guardian.dues', ['academic_year_id' => $otherYear->id]))
        ->assertOk()->assertDontSee('Allowed due');
});

it('shows only explicitly linked students and their due items', function () {
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();
    $visibleStudent = Student::factory()->for($family)->create(['name' => 'Visible Student']);
    $hiddenStudent = Student::factory()->for($family)->create(['name' => 'Hidden Student']);
    $guardian->students()->attach($visibleStudent);
    StudentDueItem::factory()->for($visibleStudent)->create(['description' => 'Visible due', 'net_amount' => 10, 'balance_amount' => 10]);
    StudentDueItem::factory()->for($hiddenStudent)->create(['description' => 'Hidden due', 'net_amount' => 10, 'balance_amount' => 10]);
    $user = User::factory()->create(['guardian_id' => $guardian->id, 'email_verified_at' => now(), 'is_active' => true]);

    $this->actingAs($user)->get(route('guardian.portal'))
        ->assertOk()
        ->assertSee('Visible Student')
        ->assertSee('Visible due')
        ->assertDontSee('Hidden Student')
        ->assertDontSee('Hidden due')
        ->assertSee(route('guardian.portal', [], false), false)
        ->assertDontSee(route('payments.collect', [], false), false);
});
