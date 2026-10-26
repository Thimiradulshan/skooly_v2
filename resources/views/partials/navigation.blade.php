@php
    $isGuardian = $currentUser->guardian_id !== null;
    $isTeacher = $currentUser->hasRole(\App\Models\Role::TEACHER);
    $isAdmin = $currentUser->hasRole(\App\Models\Role::ADMIN);
    $isAccountant = $currentUser->hasRole(\App\Models\Role::ACCOUNTANT);
    $isSuperadminOnly = $currentUser->hasRole(\App\Models\Role::SUPERADMIN) && ! $isAdmin;
@endphp

@if ($isGuardian)
    <details class="sidebar-group" open>
        <summary class="sidebar-section-title">Guardian Portal</summary>
        <div class="sidebar-group-links">
            <a class="sidebar-link {{ request()->routeIs('guardian.portal') ? 'is-active' : '' }}" href="{{ route('guardian.portal') }}" @if(request()->routeIs('guardian.portal')) aria-current="page" @endif>Dashboard</a>
            <a class="sidebar-link {{ request()->routeIs('guardian.dues') ? 'is-active' : '' }}" href="{{ route('guardian.dues') }}" @if(request()->routeIs('guardian.dues')) aria-current="page" @endif>Fees &amp; Dues</a>
            <a class="sidebar-link {{ request()->routeIs('guardian.payments') ? 'is-active' : '' }}" href="{{ route('guardian.payments') }}" @if(request()->routeIs('guardian.payments')) aria-current="page" @endif>Payment History</a>
        </div>
    </details>
@endif

@if ($isTeacher)
    <details class="sidebar-group" open>
        <summary class="sidebar-section-title">Teacher Portal</summary>
        <div class="sidebar-group-links">
            <a class="sidebar-link {{ request()->routeIs('teacher.portal') ? 'is-active' : '' }}" href="{{ route('teacher.portal') }}" @if(request()->routeIs('teacher.portal')) aria-current="page" @endif>Dashboard</a>
            <a class="sidebar-link {{ request()->routeIs('teacher.classes') ? 'is-active' : '' }}" href="{{ route('teacher.classes') }}" @if(request()->routeIs('teacher.classes')) aria-current="page" @endif>My Classes</a>
            <a class="sidebar-link {{ request()->routeIs('teacher.subjects') ? 'is-active' : '' }}" href="{{ route('teacher.subjects') }}" @if(request()->routeIs('teacher.subjects')) aria-current="page" @endif>My Subjects</a>
            <a class="sidebar-link {{ request()->routeIs('teacher.students.*') ? 'is-active' : '' }}" href="{{ route('teacher.students') }}" @if(request()->routeIs('teacher.students.*')) aria-current="page" @endif>My Students</a>
        </div>
    </details>
@endif

@if ($isSuperadminOnly)
    <details class="sidebar-group" open>
        <summary class="sidebar-section-title">Administration</summary>
        <div class="sidebar-group-links">
            <a class="sidebar-link {{ request()->routeIs('users.*') ? 'is-active' : '' }}" href="{{ route('users.index') }}" @if(request()->routeIs('users.*')) aria-current="page" @endif>Users</a>
        </div>
    </details>
@endif

@if ($isAdmin)
    <details class="sidebar-group" open>
        <summary class="sidebar-section-title">Overview</summary>
        <div class="sidebar-group-links">
            <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>Dashboard</a>
        </div>
    </details>

    <details class="sidebar-group" open>
        <summary class="sidebar-section-title">Academic Setup</summary>
        <div class="sidebar-group-links">
            <a class="sidebar-link {{ request()->routeIs('academic-years.*') ? 'is-active' : '' }}" href="{{ route('academic-years.index') }}">Academic Years</a>
            <a class="sidebar-link {{ request()->routeIs('terms.*') ? 'is-active' : '' }}" href="{{ route('terms.index') }}">Terms</a>
            <a class="sidebar-link {{ request()->routeIs('grades.*') ? 'is-active' : '' }}" href="{{ route('grades.index') }}">Grades</a>
            <a class="sidebar-link {{ request()->routeIs('sections.*') ? 'is-active' : '' }}" href="{{ route('sections.index') }}">Sections</a>
            <a class="sidebar-link {{ request()->routeIs('subjects.*') ? 'is-active' : '' }}" href="{{ route('subjects.index') }}">Subjects</a>
            <a class="sidebar-link {{ request()->routeIs('school-settings.*') ? 'is-active' : '' }}" href="{{ route('school-settings.edit') }}">School Settings</a>
        </div>
    </details>

    <details class="sidebar-group" open>
        <summary class="sidebar-section-title">Students &amp; Families</summary>
        <div class="sidebar-group-links">
            <a class="sidebar-link {{ request()->routeIs('families.*', 'guardians.*') ? 'is-active' : '' }}" href="{{ route('families.index') }}">Families &amp; Guardians</a>
            <a class="sidebar-link {{ request()->routeIs('students.*', 'enrollments.*') ? 'is-active' : '' }}" href="{{ route('students.index') }}">Students</a>
        </div>
    </details>

    <details class="sidebar-group" open>
        <summary class="sidebar-section-title">Staff &amp; Teachers</summary>
        <div class="sidebar-group-links">
            <a class="sidebar-link {{ request()->routeIs('users.*') ? 'is-active' : '' }}" href="{{ route('users.index') }}">Users</a>
            <a class="sidebar-link {{ request()->routeIs('teacher-qualifications.*') ? 'is-active' : '' }}" href="{{ route('teacher-qualifications.index') }}">Qualifications</a>
            <a class="sidebar-link {{ request()->routeIs('teacher-assignments.*') ? 'is-active' : '' }}" href="{{ route('teacher-assignments.index') }}">Teaching Assignments</a>
            <a class="sidebar-link {{ request()->routeIs('section-year-assignments.*') ? 'is-active' : '' }}" href="{{ route('section-year-assignments.index') }}">Class In Charge</a>
        </div>
    </details>
@endif

@if ($isAdmin || $isAccountant)
    <details class="sidebar-group" open>
        <summary class="sidebar-section-title">Finance</summary>
        <div class="sidebar-group-links">
            @if ($isAdmin)
                <a class="sidebar-link {{ request()->routeIs('fee-categories.*') ? 'is-active' : '' }}" href="{{ route('fee-categories.index') }}">Fee Categories</a>
                <a class="sidebar-link {{ request()->routeIs('fee-structures.*') ? 'is-active' : '' }}" href="{{ route('fee-structures.index') }}">Fee Structures</a>
                <a class="sidebar-link {{ request()->routeIs('due-generation.recurring.*') ? 'is-active' : '' }}" href="{{ route('due-generation.recurring.create') }}">Recurring Generation</a>
                <a class="sidebar-link {{ request()->routeIs('due-generation.events.*') ? 'is-active' : '' }}" href="{{ route('due-generation.events.create') }}">Event Generation</a>
            @endif
            <a class="sidebar-link {{ request()->routeIs('payments.collect', 'families.payments.*') ? 'is-active' : '' }}" href="{{ route('payments.collect') }}">Collect Payment</a>
            <a class="sidebar-link {{ request()->routeIs('payments.index', 'payments.show') ? 'is-active' : '' }}" href="{{ route('payments.index') }}">Payment History</a>
            <a class="sidebar-link {{ request()->routeIs('receipts.*') ? 'is-active' : '' }}" href="{{ route('receipts.index') }}">Receipts</a>
            <a class="sidebar-link {{ request()->routeIs('payment-reversals.*', 'payments.reversals.*') ? 'is-active' : '' }}" href="{{ route('payment-reversals.index') }}">Reversals</a>
            <a class="sidebar-link {{ request()->routeIs('dues-dashboard.*') ? 'is-active' : '' }}" href="{{ route('dues-dashboard.index') }}">Dues Dashboard</a>
            <a class="sidebar-link {{ request()->routeIs('payment-reminders.*') ? 'is-active' : '' }}" href="{{ route('payment-reminders.index') }}">Payment Reminders</a>
        </div>
    </details>
@endif

@if ($isAdmin)
    <details class="sidebar-group" open>
        <summary class="sidebar-section-title">Events &amp; Promotions</summary>
        <div class="sidebar-group-links">
            <a class="sidebar-link {{ request()->routeIs('events.*') ? 'is-active' : '' }}" href="{{ route('events.index') }}">Events</a>
            <a class="sidebar-link {{ request()->routeIs('promotion-batches.*') ? 'is-active' : '' }}" href="{{ route('promotion-batches.index') }}">Promotion</a>
        </div>
    </details>

    <details class="sidebar-group" open>
        <summary class="sidebar-section-title">Reports &amp; Audit</summary>
        <div class="sidebar-group-links">
            <a class="sidebar-link {{ request()->routeIs('audit-logs.*') ? 'is-active' : '' }}" href="{{ route('audit-logs.index') }}">Audit Log</a>
        </div>
    </details>
@endif
