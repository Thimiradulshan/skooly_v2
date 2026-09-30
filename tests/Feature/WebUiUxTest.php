<?php

use App\Models\Family;
use App\Models\Role;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

it('renders a login page with Skooly branding', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Skooly')
        ->assertSee('School Management Admin')
        ->assertSee('Sign in');
});

it('renders email and password fields on the login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('type="password"', false);
});

it('renders the login page inside a login card and loads the shared stylesheet', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('auth-card', false)
        ->assertSee('css/admin.css', false);
});

it('loads the admin dashboard with the workflow groups', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Registration')
        ->assertSee('Fees &amp; dues', false)
        ->assertSee('Payments')
        ->assertSee('Events &amp; promotion', false)
        ->assertSee('Reminders')
        ->assertSee('Draft promotion batches');
});

it('shows the main workflow links in the admin navigation', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(route('admin.dashboard', [], false), false)
        ->assertSee(route('families.index', [], false), false)
        ->assertSee(route('fee-categories.index', [], false), false)
        ->assertSee(route('fee-structures.index', [], false), false)
        ->assertSee(route('due-generation.recurring.create', [], false), false)
        ->assertSee(route('due-generation.events.create', [], false), false)
        ->assertSee(route('dues-dashboard.index', [], false), false)
        ->assertSee(route('events.index', [], false), false)
        ->assertSee(route('promotion-batches.index', [], false), false)
        ->assertSee(route('payment-reminders.index', [], false), false);
});

it('shows the signed in admin and a logout control', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee($admin->email)
        ->assertSee('Sign out')
        ->assertSee(route('logout', [], false), false);
});

it('shows the login page to a guest with no admin navigation', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('sidebar-nav', false)
        ->assertDontSee('Sign out');
});

it('denies a non-admin the admin dashboard and its navigation', function () {
    $this->actingAs(userWithRole(Role::ACCOUNTANT))
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs(userWithRole(Role::TEACHER))
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('still loads the main admin index pages after the redesign', function () {
    $admin = adminUser();
    $family = Family::factory()->create();
    $pages = [
        route('families.index'),
        route('fee-categories.index'),
        route('fee-structures.index'),
        route('dues-dashboard.index'),
        route('payment-reminders.index'),
        route('payment-reminders.create'),
        route('events.index'),
        route('promotion-batches.index'),
        route('due-generation.recurring.create'),
        route('due-generation.events.create'),
        route('families.create'),
        route('families.show', $family),
        route('families.students.create', $family),
        route('families.payments.create', $family),
    ];

    foreach ($pages as $page) {
        $this->actingAs($admin)->get($page)->assertOk();
    }
});

it('renders the shared card, table, and empty state structure', function () {
    $this->actingAs(adminUser())
        ->get(route('families.index'))
        ->assertOk()
        ->assertSee('table-wrap', false)
        ->assertSee('No families yet.', false);

    $this->actingAs(adminUser())
        ->get(route('fee-categories.index'))
        ->assertOk()
        ->assertSee('No fee categories yet.', false);
});

it('keeps the destructive routes absent after the redesign', function () {
    foreach ([
        'families.destroy',
        'payments.destroy',
        'receipts.destroy',
        'promotion-batches.destroy',
        'payment-reminders.destroy',
        'payment-reminders.send',
    ] as $name) {
        expect(Route::has($name))->toBeFalse();
    }
});
