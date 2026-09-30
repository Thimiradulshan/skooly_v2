<?php

use App\Models\Family;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\Student;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

it('redirects a guest visiting the root route to login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('redirects an admin visiting the root route to the dashboard', function () {
    $this->actingAs(adminUser())->get('/')->assertRedirect(route('admin.dashboard'));
});

it('blocks a guest from the admin dashboard', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

it('blocks teachers and accountants from the admin dashboard', function () {
    $this->actingAs(userWithRole(Role::TEACHER))->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->get(route('admin.dashboard'))->assertForbidden();
});

it('shows an admin the dashboard with links to every main workflow', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee(route('families.index', [], false))
        ->assertSee(route('fee-categories.index', [], false))
        ->assertSee(route('fee-structures.index', [], false))
        ->assertSee(route('due-generation.recurring.create', [], false))
        ->assertSee(route('due-generation.events.create', [], false))
        ->assertSee(route('dues-dashboard.index', [], false))
        ->assertSee(route('payment-reminders.index', [], false))
        ->assertSee(route('events.index', [], false))
        ->assertSee(route('promotion-batches.index', [], false));
});

it('shows cheap workflow counts on the dashboard', function () {
    Family::factory()->create();

    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Families')
        ->assertSee('Students')
        ->assertSee('Outstanding due items')
        ->assertSee('Draft promotion batches')
        ->assertSee('Payment reminders');
});

it('renders the main workflow links in the admin layout navigation', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertSee(route('admin.dashboard', [], false))
        ->assertSee(route('families.index', [], false))
        ->assertSee(route('fee-categories.index', [], false))
        ->assertSee(route('due-generation.recurring.create', [], false))
        ->assertSee(route('dues-dashboard.index', [], false))
        ->assertSee(route('events.index', [], false))
        ->assertSee(route('promotion-batches.index', [], false))
        ->assertSee(route('payment-reminders.index', [], false));
});

it('keeps the logout control visible for an authenticated admin', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(route('logout', [], false))
        ->assertSee('Sign out');
});

it('shows workflow links on the family show page', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    Guardian::factory()->for($family)->create();

    $this->actingAs(adminUser())
        ->get(route('families.show', $family))
        ->assertOk()
        ->assertSee(route('families.students.create', $family, false))
        ->assertSee(route('families.payments.create', $family, false))
        ->assertSee(route('students.discounts.create', $student, false))
        ->assertSee(route('students.fee-subscriptions.create', $student, false));
});

it('shows a cross-link to fee structure creation from fee categories', function () {
    $this->actingAs(adminUser())
        ->get(route('fee-categories.index'))
        ->assertOk()
        ->assertSee(route('fee-structures.create', [], false));
});

it('shows cross-links on the dues dashboard', function () {
    $this->actingAs(adminUser())
        ->get(route('dues-dashboard.index'))
        ->assertOk()
        ->assertSee(route('due-generation.recurring.create', [], false))
        ->assertSee(route('payment-reminders.index', [], false));
});

it('still loads the dues dashboard and payment reminder index after navigation changes', function () {
    $this->actingAs(adminUser())->get(route('dues-dashboard.index'))->assertOk();
    $this->actingAs(adminUser())->get(route('payment-reminders.index'))->assertOk();
});

it('shows an empty state when no families exist', function () {
    $this->actingAs(adminUser())
        ->get(route('families.index'))
        ->assertOk()
        ->assertSee('No families yet', false);
});

it('renders a success flash message from session', function () {
    $this->withSession(['status' => 'Family created.'])
        ->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Family created.');
});

it('renders an error flash message from session', function () {
    $this->withSession(['error' => 'Something went wrong.'])
        ->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Something went wrong.');
});

it('adds no delete or API routes', function () {
    expect(Route::has('admin.destroy'))->toBeFalse();
    expect(Route::has('admin.edit'))->toBeFalse();

    $apiRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'api/'));

    expect($apiRoutes)->toBeEmpty();
});
