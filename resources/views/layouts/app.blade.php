<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Skooly') &middot; Skooly</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
@auth
    <input class="sidebar-toggle-control" type="checkbox" id="sidebar-toggle" aria-hidden="true">
    <label class="sidebar-backdrop" for="sidebar-toggle" aria-label="Close navigation"></label>

    <div class="app-shell">
        <aside class="sidebar">
            <a class="sidebar-brand" href="{{ route('admin.dashboard') }}">
                <span class="brand-mark">S</span>
                <span class="brand-copy">
                    <span class="brand-name">Skooly</span>
                    <span class="sidebar-tagline">School Management</span>
                </span>
            </a>

            <nav class="sidebar-nav" aria-label="Main navigation">
                @if (auth()->user()->hasRole(\App\Models\Role::ADMIN))
                <div>
                    <p class="sidebar-section-title">Overview</p>
                    <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
                       href="{{ route('admin.dashboard') }}">Dashboard</a>
                </div>

                <div>
                    <p class="sidebar-section-title">Registration</p>
                    <a class="sidebar-link {{ request()->routeIs('families.*', 'students.*') ? 'is-active' : '' }}"
                       href="{{ route('families.index') }}">Families &amp; Students</a>
                </div>

                <div>
                    <p class="sidebar-section-title">Staff</p>
                    <a class="sidebar-link {{ request()->routeIs('users.*') ? 'is-active' : '' }}"
                       href="{{ route('users.index') }}">Users</a>
                </div>

                <div>
                    <p class="sidebar-section-title">Academic Setup</p>
                    <a class="sidebar-link {{ request()->routeIs('academic-years.*') ? 'is-active' : '' }}"
                       href="{{ route('academic-years.index') }}">Academic Years</a>
                    <a class="sidebar-link {{ request()->routeIs('terms.*') ? 'is-active' : '' }}"
                       href="{{ route('terms.index') }}">Terms</a>
                    <a class="sidebar-link {{ request()->routeIs('grades.*') ? 'is-active' : '' }}"
                       href="{{ route('grades.index') }}">Grades</a>
                    <a class="sidebar-link {{ request()->routeIs('sections.*') ? 'is-active' : '' }}"
                       href="{{ route('sections.index') }}">Sections</a>
                    <a class="sidebar-link {{ request()->routeIs('school-settings.*') ? 'is-active' : '' }}"
                       href="{{ route('school-settings.edit') }}">School Settings</a>
                    <a class="sidebar-link {{ request()->routeIs('subjects.*') ? 'is-active' : '' }}"
                       href="{{ route('subjects.index') }}">Subjects</a>
                    <a class="sidebar-link {{ request()->routeIs('teacher-qualifications.*') ? 'is-active' : '' }}"
                       href="{{ route('teacher-qualifications.index') }}">Teacher Qualifications</a>
                    <a class="sidebar-link {{ request()->routeIs('teacher-assignments.*') ? 'is-active' : '' }}"
                       href="{{ route('teacher-assignments.index') }}">Teaching Assignments</a>
                    <a class="sidebar-link {{ request()->routeIs('section-year-assignments.*') ? 'is-active' : '' }}"
                       href="{{ route('section-year-assignments.index') }}">Class In Charge</a>
                </div>

                <div>
                    <p class="sidebar-section-title">Fees &amp; Dues</p>
                    <a class="sidebar-link {{ request()->routeIs('fee-categories.*') ? 'is-active' : '' }}"
                       href="{{ route('fee-categories.index') }}">Fee Categories</a>
                    <a class="sidebar-link {{ request()->routeIs('fee-structures.*') ? 'is-active' : '' }}"
                       href="{{ route('fee-structures.index') }}">Fee Structures</a>
                    <a class="sidebar-link {{ request()->routeIs('due-generation.recurring.*') ? 'is-active' : '' }}"
                       href="{{ route('due-generation.recurring.create') }}">Recurring Generation</a>
                    <a class="sidebar-link {{ request()->routeIs('due-generation.events.*') ? 'is-active' : '' }}"
                       href="{{ route('due-generation.events.create') }}">Event Generation</a>
                    <a class="sidebar-link {{ request()->routeIs('dues-dashboard.*') ? 'is-active' : '' }}"
                        href="{{ route('dues-dashboard.index') }}">Dues Dashboard</a>
                </div>
                @endif

                <div>
                    <p class="sidebar-section-title">Payments</p>
                    <a class="sidebar-link {{ request()->routeIs('payments.collect', 'families.payments.*') ? 'is-active' : '' }}"
                       href="{{ route('payments.collect') }}">Collect Payment</a>
                    <a class="sidebar-link {{ request()->routeIs('payments.*') ? 'is-active' : '' }}"
                       href="{{ route('payments.index') }}">Payment History</a>
                    <a class="sidebar-link {{ request()->routeIs('receipts.*') ? 'is-active' : '' }}"
                        href="{{ route('receipts.index') }}">Receipts</a>
                    <a class="sidebar-link {{ request()->routeIs('payment-reversals.*') ? 'is-active' : '' }}"
                        href="{{ route('payment-reversals.index') }}">Reversals</a>
                    <a class="sidebar-link {{ request()->routeIs('dues-dashboard.*') ? 'is-active' : '' }}"
                       href="{{ route('dues-dashboard.index') }}">Dues Dashboard</a>
                    <a class="sidebar-link {{ request()->routeIs('payment-reminders.*') ? 'is-active' : '' }}"
                       href="{{ route('payment-reminders.index') }}">Payment Reminders</a>
                </div>

                @if (auth()->user()->hasRole(\App\Models\Role::ADMIN))
                <div>
                    <p class="sidebar-section-title">School Life</p>
                    <a class="sidebar-link {{ request()->routeIs('events.*') ? 'is-active' : '' }}"
                       href="{{ route('events.index') }}">Events</a>
                    <a class="sidebar-link {{ request()->routeIs('promotion-batches.*') ? 'is-active' : '' }}"
                       href="{{ route('promotion-batches.index') }}">Promotion</a>
                </div>

                <div>
                    <p class="sidebar-section-title">Communication</p>
                </div>

                <div>
                    <p class="sidebar-section-title">Governance</p>
                    <a class="sidebar-link {{ request()->routeIs('audit-logs.*') ? 'is-active' : '' }}"
                        href="{{ route('audit-logs.index') }}">Audit Log</a>
                </div>
                @endif
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-user-wrap">
                    <span class="user-avatar">{{ substr((string) auth()->user()->name, 0, 1) }}</span>
                    <p class="sidebar-user">
                        {{ auth()->user()->name }}
                        <span class="sidebar-role">{{ auth()->user()->email }}</span>
                    </p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-small">Sign out</button>
                </form>
            </div>
        </aside>

        <div class="app-main">
            <header class="topbar">
                <div class="topbar-context">
                    <label class="menu-toggle" for="sidebar-toggle" aria-label="Open navigation">
                        <span class="menu-toggle-lines"></span>
                    </label>
                    <p class="topbar-eyebrow">Admin workspace</p>
                </div>
                <span class="topbar-date">{{ now()->format('l, F j') }}</span>
            </header>

            <main class="app-content">
                @include('partials.flash')
                @yield('content')
            </main>

            <footer class="app-footer">Skooly Stage 1 admin &middot; Internal use</footer>
        </div>
    </div>
@else
    <div class="auth-shell">
        @include('partials.flash')
        @yield('content')
    </div>
@endauth

<script src="{{ asset('js/admin-ui.js') }}" defer></script>
</body>
</html>
