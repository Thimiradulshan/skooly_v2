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
    @php
        $currentUser = auth()->user();
        $homeRoute = route($currentUser->homeRouteName());
        $workspaceLabel = match (true) {
            $currentUser->hasRole(\App\Models\Role::SUPERADMIN) => 'Administration workspace',
            $currentUser->hasRole(\App\Models\Role::ADMIN) => 'Admin workspace',
            $currentUser->hasRole(\App\Models\Role::ACCOUNTANT) => 'Finance workspace',
            $currentUser->guardian_id !== null => 'Guardian portal',
            $currentUser->hasRole(\App\Models\Role::TEACHER) => 'Teacher portal',
            default => 'My account',
        };
    @endphp
    <button class="sidebar-backdrop" type="button" data-sidebar-close aria-label="Close navigation" tabindex="-1"></button>

    <div class="app-shell">
        <aside class="sidebar" id="primary-navigation" aria-label="Workspace navigation">
            <a class="sidebar-brand" href="{{ $homeRoute }}">
                    <span class="brand-mark" aria-hidden="true"><span></span><span></span></span>
                <span class="brand-copy">
                    <span class="brand-name">Skooly</span>
                    <span class="sidebar-tagline">School Management</span>
                </span>
            </a>

            <nav class="sidebar-nav" aria-label="Main navigation">
                @include('partials.navigation', ['currentUser' => $currentUser])
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
                <a class="sidebar-link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}" href="{{ route('profile.edit') }}">My profile</a>
                <a class="sidebar-link {{ request()->routeIs('two-factor.*') ? 'is-active' : '' }}" href="{{ route('two-factor.show') }}">Security</a>
            </div>
        </aside>

        <div class="app-main">
            <header class="topbar">
                <div class="topbar-context">
                    <button class="menu-toggle" type="button" data-sidebar-open aria-controls="primary-navigation" aria-expanded="false" aria-label="Open navigation">
                        <span class="menu-toggle-lines"></span>
                    </button>
                    <button class="sidebar-collapse" type="button" data-sidebar-collapse aria-controls="primary-navigation" aria-expanded="true" aria-label="Collapse navigation">‹</button>
                    <nav class="topbar-breadcrumb" aria-label="Page context"><span>{{ $workspaceLabel }}</span><span aria-hidden="true">/</span><strong>@yield('title', 'Dashboard')</strong></nav>
                </div>
                <div class="topbar-account">
                    <span class="topbar-date">{{ now()->format('l, F j') }}</span>
                    <a class="topbar-profile" href="{{ route('profile.edit') }}" aria-label="Open profile for {{ $currentUser->name }}">
                        <span class="topbar-profile-avatar">{{ substr((string) $currentUser->name, 0, 1) }}</span>
                        <span class="topbar-profile-copy">
                            <span class="topbar-profile-name">{{ $currentUser->name }}</span>
                            <span class="topbar-profile-role">{{ $workspaceLabel }}</span>
                        </span>
                    </a>
                </div>
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
