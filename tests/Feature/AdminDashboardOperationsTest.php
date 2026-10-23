<?php

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Family;
use App\Models\Payment;
use App\Models\PaymentReminder;
use App\Models\PaymentReversal;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentDueItem;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;

uses(LazilyRefreshDatabase::class);

it('shows accurate operational dashboard data for the active academic year', function () {
    Carbon::setTestNow('2026-10-22 12:00:00');
    $currentYear = AcademicYear::factory()->create(['name' => '2026 Academic Year']);
    $historicalYear = AcademicYear::factory()->create(['name' => '2025 Academic Year']);
    setActiveAcademicYear($currentYear);

    $family = Family::factory()->create();
    $activeStudent = Student::factory()->for($family)->create(['status' => Student::STATUS_ACTIVE]);
    $inactiveStudent = Student::factory()->create(['status' => Student::STATUS_INACTIVE]);
    $teacher = userWithRole(Role::TEACHER);
    User::factory()->create(['is_active' => false])->roles()->attach(Role::query()->firstOrCreate(['name' => Role::TEACHER]));

    StudentDueItem::factory()->for($activeStudent)->for($currentYear)->create([
        'original_amount' => '125.50',
        'net_amount' => '125.50',
        'balance_amount' => '125.50',
        'due_date' => '2026-10-01',
    ]);
    StudentDueItem::factory()->for($inactiveStudent)->for($currentYear)->create([
        'original_amount' => '50.00',
        'net_amount' => '50.00',
        'balance_amount' => '50.00',
        'due_date' => '2026-11-01',
    ]);
    StudentDueItem::factory()->for($activeStudent)->for($historicalYear)->create([
        'original_amount' => '999.00',
        'net_amount' => '999.00',
        'balance_amount' => '999.00',
        'due_date' => '2026-01-01',
    ]);
    Payment::factory()->for($family)->create(['amount' => '75.25', 'paid_at' => '2026-10-10']);
    Payment::factory()->for($family)->create(['amount' => '999.00', 'paid_at' => '2026-09-30']);
    PaymentReversal::factory()->create(['status' => PaymentReversal::STATUS_REQUESTED]);
    PaymentReminder::factory()->create(['status' => PaymentReminder::STATUS_PENDING]);
    AuditLog::factory()->for($teacher, 'actor')->for($family, 'auditable')->create([
        'action' => AuditLog::ACTION_FAMILY_CREATED,
        'occurred_at' => now(),
    ]);

    $response = $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('2026 Academic Year')
        ->assertSee('Registered students')
        ->assertSee('2')
        ->assertSee('Active students')
        ->assertSee('Active teachers')
        ->assertSee('175.50')
        ->assertSee('125.50')
        ->assertSee('75.25')
        ->assertSee('Payment reversal requests')
        ->assertSee('Pending reminder records')
        ->assertSee('Family Created');

    $response->assertViewHas('studentCount', 2)
        ->assertViewHas('activeStudentCount', 1)
        ->assertViewHas('teacherCount', 1)
        ->assertViewHas('outstandingBalance', '175.50')
        ->assertViewHas('overdueBalance', '125.50')
        ->assertViewHas('studentsWithOutstandingBalance', 2)
        ->assertViewHas('paymentsCollectedThisMonth', '75.25')
        ->assertViewHas('pendingReversalCount', 1)
        ->assertViewHas('pendingReminderCount', 1);

    Carbon::setTestNow();
});

it('handles an empty database with an actionable dashboard', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Not configured')
        ->assertSee('0.00')
        ->assertSee('No audit activity has been recorded yet.')
        ->assertSee(route('families.create'), false)
        ->assertSee(route('students.index'), false)
        ->assertSee(route('payments.collect'), false)
        ->assertSee(route('academic-years.index'), false)
        ->assertSee(route('dues-dashboard.index'), false);
});

it('keeps recent activity and dashboard data restricted to administrators', function () {
    AuditLog::factory()->create(['action' => AuditLog::ACTION_PAYMENT_RECORDED]);

    $this->actingAs(userWithRole(Role::ACCOUNTANT))->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs(userWithRole(Role::TEACHER))->get(route('admin.dashboard'))->assertForbidden();
});
