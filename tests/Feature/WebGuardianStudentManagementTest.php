<?php

use App\Models\AuditLog;
use App\Models\Family;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\Student;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('lets an admin add and edit a guardian without granting student visibility', function () {
    $family = Family::factory()->create();

    $this->actingAs(adminUser())->post(route('families.guardians.store', $family), [
        'name' => 'Second Guardian', 'relationship' => 'Parent', 'email' => 'guardian@example.test',
    ])->assertRedirect();

    $guardian = Guardian::query()->sole();

    $this->actingAs(adminUser())->put(route('guardians.update', $guardian), [
        'name' => 'Updated Guardian', 'relationship' => 'Parent', 'email' => 'updated@example.test',
    ])->assertRedirect(route('guardians.show', $guardian));

    expect($guardian->refresh()->students)->toHaveCount(0);
});

it('links and auditedly unlinks a guardian from a student in the same family', function () {
    $admin = adminUser();
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();
    $student = Student::factory()->for($family)->create();

    $this->actingAs($admin)->post(route('students.guardians.store', $student), ['guardian_id' => $guardian->id])
        ->assertSessionHas('status', 'Guardian linked to student.');

    expect($guardian->refresh()->students->sole()->is($student))->toBeTrue();

    $this->actingAs($admin)->delete(route('students.guardians.destroy', [$student, $guardian]))
        ->assertSessionHas('status', 'Guardian access revoked.');

    expect($guardian->refresh()->students)->toHaveCount(0);
    $this->assertDatabaseHas('audit_logs', [
        'action' => AuditLog::ACTION_GUARDIAN_STUDENT_UNLINKED,
        'actor_user_id' => $admin->id,
        'auditable_id' => $guardian->id,
    ]);
});

it('refuses guardian links across families', function () {
    $guardian = Guardian::factory()->for(Family::factory())->create();
    $student = Student::factory()->for(Family::factory())->create();

    $this->actingAs(adminUser())->post(route('students.guardians.store', $student), ['guardian_id' => $guardian->id])
        ->assertSessionHasErrors('guardian_id');

    expect($guardian->students)->toHaveCount(0);
});

it('blocks teachers and accountants from guardian management', function () {
    $family = Family::factory()->create();

    $this->actingAs(userWithRole(Role::TEACHER))->get(route('families.guardians.create', $family))->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->post(route('families.guardians.store', $family), [])->assertForbidden();
});
