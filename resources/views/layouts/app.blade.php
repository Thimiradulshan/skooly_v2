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
    <div class="app-shell">
        <aside class="sidebar">
            <a class="sidebar-brand" href="{{ route('admin.dashboard') }}">
                Skooly
                <span class="sidebar-tagline">School Management</span>
            </a>

            <nav class="sidebar-nav" aria-label="Main">
                <div>
                    <p class="sidebar-section-title">Overview</p>
                    <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
                       href="{{ route('admin.dashboard') }}">Dashboard</a>
                </div>

                <div>
                    <p class="sidebar-section-title">People</p>
                    <a class="sidebar-link {{ request()->routeIs('families.*') ? 'is-active' : '' }}"
                       href="{{ route('families.index') }}">Families</a>
                </div>

                <div>
                    <p class="sidebar-section-title">Money</p>
                    <a class="sidebar-link {{ request()->routeIs('fee-categories.*') ? 'is-active' : '' }}"
                       href="{{ route('fee-categories.index') }}">Fee Categories</a>
                    <a class="sidebar-link {{ request()->routeIs('fee-structures.*') ? 'is-active' : '' }}"
                       href="{{ route('fee-structures.index') }}">Fee Structures</a>
                </div>

                <div>
                    <p class="sidebar-section-title">Dues</p>
                    <a class="sidebar-link {{ request()->routeIs('due-generation.*') ? 'is-active' : '' }}"
                       href="{{ route('due-generation.recurring.create') }}">Recurring Due Generation</a>
                    <a class="sidebar-link {{ request()->routeIs('due-generation.events.*') ? 'is-active' : '' }}"
                       href="{{ route('due-generation.events.create') }}">Event Due Generation</a>
                    <a class="sidebar-link {{ request()->routeIs('dues-dashboard.*') ? 'is-active' : '' }}"
                       href="{{ route('dues-dashboard.index') }}">Dues Dashboard</a>
                </div>

                <div>
                    <p class="sidebar-section-title">School Life</p>
                    <a class="sidebar-link {{ request()->routeIs('events.*') ? 'is-active' : '' }}"
                       href="{{ route('events.index') }}">Events</a>
                    <a class="sidebar-link {{ request()->routeIs('promotion-batches.*') ? 'is-active' : '' }}"
                       href="{{ route('promotion-batches.index') }}">Promotion</a>
                    <a class="sidebar-link {{ request()->routeIs('payment-reminders.*') ? 'is-active' : '' }}"
                       href="{{ route('payment-reminders.index') }}">Reminders</a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <p class="sidebar-user">Signed in as {{ auth()->user()->email }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-small">Sign out</button>
                </form>
            </div>
        </aside>

        <div class="app-main">
            <main class="app-content">
                @include('partials.flash')

                @yield('content')
            </main>

            <footer class="app-footer">Skooly Stage 1 admin</footer>
        </div>
    </div>
@else
    <div class="auth-shell">
        <div>
            @include('partials.flash')
            @yield('content')
        </div>
    </div>
@endauth
</body>
</html>
