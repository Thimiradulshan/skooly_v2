<?php

use App\Models\Family;
use App\Models\Role;
use App\Models\Student;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

it('shows the login page with branding and a login card', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Skooly')
        ->assertSee('School Management Admin')
        ->assertSee('data-testid="login-card"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('Sign in');
});

it('loads the admin dashboard with stat cards and workflow groups', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-testid="dashboard-metrics"', false)
        ->assertSee('Operations overview')
        ->assertSee('Registration')
        ->assertSee('Fees &amp; dues', false)
        ->assertSee('Payments')
        ->assertSee('Events &amp; promotion', false)
        ->assertSee('Reminders');
});

it('renders the admin shell with sidebar and topbar', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('sidebar-nav', false)
        ->assertSee('topbar', false)
        ->assertSee('sidebar-section-title', false)
        ->assertSee('sidebar-footer', false);
});

it('keeps every main workflow reachable from the dashboard', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(route('families.index', [], false), false)
        ->assertSee(route('families.create', [], false), false)
        ->assertSee(route('fee-categories.index', [], false), false)
        ->assertSee(route('fee-structures.index', [], false), false)
        ->assertSee(route('due-generation.recurring.create', [], false), false)
        ->assertSee(route('due-generation.events.create', [], false), false)
        ->assertSee(route('dues-dashboard.index', [], false), false)
        ->assertSee(route('events.index', [], false), false)
        ->assertSee(route('promotion-batches.index', [], false), false)
        ->assertSee(route('payment-reminders.index', [], false), false);
});

it('loads the family list, detail, and registration pages', function () {
    $admin = adminUser();
    $family = Family::factory()->create();
    Student::factory()->for($family)->create();

    $this->actingAs($admin)->get(route('families.index'))->assertOk();
    $this->actingAs($admin)->get(route('families.show', $family))->assertOk();
    $this->actingAs($admin)->get(route('families.students.create', $family))->assertOk();
});

it('loads the fee category and fee structure pages', function () {
    $admin = adminUser();

    $this->actingAs($admin)->get(route('fee-categories.index'))->assertOk();
    $this->actingAs($admin)->get(route('fee-categories.create'))->assertOk();
    $this->actingAs($admin)->get(route('fee-structures.index'))->assertOk();
    $this->actingAs($admin)->get(route('fee-structures.create'))->assertOk();
});

it('loads the dues dashboard with report sections', function () {
    $this->actingAs(adminUser())
        ->get(route('dues-dashboard.index'))
        ->assertOk()
        ->assertSee('Outstanding balance')
        ->assertSee('Total due')
        ->assertSee('Total collected');
});

it('loads the payment collection page when a family exists', function () {
    $admin = adminUser();
    $family = Family::factory()->create();

    $this->actingAs($admin)
        ->get(route('families.payments.create', $family))
        ->assertOk();
});

it('loads the event pages', function () {
    $admin = adminUser();

    $this->actingAs($admin)->get(route('events.index'))->assertOk();
    $this->actingAs($admin)->get(route('events.create'))->assertOk();
});

it('loads the promotion batch pages', function () {
    $admin = adminUser();

    $this->actingAs($admin)->get(route('promotion-batches.index'))->assertOk();
    $this->actingAs($admin)->get(route('promotion-batches.create'))->assertOk();
});

it('loads the payment reminder pages', function () {
    $admin = adminUser();

    $this->actingAs($admin)->get(route('payment-reminders.index'))->assertOk();
    $this->actingAs($admin)->get(route('payment-reminders.create'))->assertOk();
});

it('keeps the logout control available for a signed in admin', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Sign out')
        ->assertSee(route('logout', [], false), false);
});

it('shows no admin navigation to a guest', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('sidebar-nav', false)
        ->assertDontSee('Sign out');
});

it('still denies non-admins the admin workspace', function () {
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs(userWithRole(Role::TEACHER))->get(route('admin.dashboard'))->assertForbidden();
});

it('has not removed any existing web route', function () {
    $routes = [
        'login', 'login.store', 'logout',
        'admin.dashboard',
        'families.index', 'families.create', 'families.store', 'families.show', 'families.edit', 'families.update',
        'families.students.create', 'families.students.store',
        'families.payments.create', 'families.payments.store',
        'fee-categories.index', 'fee-categories.create', 'fee-categories.store', 'fee-categories.edit', 'fee-categories.update',
        'fee-structures.index', 'fee-structures.create', 'fee-structures.store',
        'students.discounts.create', 'students.discounts.store',
        'students.fee-subscriptions.create', 'students.fee-subscriptions.store',
        'due-generation.recurring.create', 'due-generation.recurring.store',
        'due-generation.events.create', 'due-generation.events.store',
        'dues-dashboard.index',
        'events.index', 'events.create', 'events.store', 'events.show', 'events.edit', 'events.update',
        'events.charges.create', 'events.charges.store',
        'events.participation.create', 'events.participation.store',
        'promotion-batches.index', 'promotion-batches.create', 'promotion-batches.store',
        'promotion-batches.show', 'promotion-batches.confirm',
        'payment-reminders.index', 'payment-reminders.create', 'payment-reminders.store', 'payment-reminders.show',
        'payments.show', 'receipts.show',
    ];

    foreach ($routes as $name) {
        expect(Route::has($name))->toBeTrue();
    }
});

it('adds no destructive or mutation routes', function () {
    $forbidden = [
        'families.destroy', 'students.destroy',
        'fee-categories.destroy', 'fee-structures.destroy',
        'payments.edit', 'payments.update', 'payments.destroy', 'payments.refund',
        'receipts.destroy', 'receipts.export',
        'events.destroy', 'events.charges.destroy', 'events.participation.destroy',
        'promotion-batches.destroy', 'promotion-batches.reverse',
        'payment-reminders.send', 'payment-reminders.edit', 'payment-reminders.update', 'payment-reminders.destroy',
    ];

    foreach ($forbidden as $name) {
        expect(Route::has($name))->toBeFalse();
    }
});
