@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <x-page-header title="Operations overview"
                   :subtitle="$currentAcademicYear ? 'Current academic year: '.$currentAcademicYear->name : 'Set an active academic year to view year-specific operational balances.'"
                   eyebrow="Today" />

    <h2 class="section-heading">School overview</h2>
    <div class="card-grid" data-testid="dashboard-metrics">
        <x-stat-card label="Families" :value="$familyCount" hint="Registered households" />
        <x-stat-card label="Registered students" :value="$studentCount" hint="All student profiles" />
        <x-stat-card label="Active students" :value="$activeStudentCount" hint="Current student status" />
        <x-stat-card label="Active teachers" :value="$teacherCount" hint="Teacher accounts enabled" />
        <x-stat-card label="Current academic year" :value="$currentAcademicYear?->name ?? 'Not configured'" hint="Operational year" />
    </div>

    <h2 class="section-heading">Financial overview</h2>
    <div class="card-grid">
        <x-stat-card label="Outstanding fees" :value="$outstandingBalance" :hint="$currentAcademicYear ? $currentAcademicYear->name.' balance' : 'No active academic year'" />
        <x-stat-card label="Overdue balances" :value="$overdueBalance" :hint="$overdueDueItemCount.' overdue due item(s)'" />
        <x-stat-card label="Collected this month" :value="$paymentsCollectedThisMonth" hint="Recorded payments by paid date" />
        <x-stat-card label="Students with balances" :value="$studentsWithOutstandingBalance" hint="Current academic year" />
    </div>

    <h2 class="section-heading">Requires attention</h2>
    <div class="card-grid">
        <a class="workflow-card" href="{{ route('payment-reversals.index') }}">
            <span>
                <span class="workflow-card-title">Payment reversal requests</span>
                <span class="workflow-card-text">{{ $pendingReversalCount }} request(s) awaiting an independent approval.</span>
            </span>
        </a>
        <a class="workflow-card" href="{{ route('dues-dashboard.index', $currentAcademicYear ? ['academic_year_id' => $currentAcademicYear->id] : []) }}">
            <span>
                <span class="workflow-card-title">Overdue fees</span>
                <span class="workflow-card-text">{{ $overdueDueItemCount }} overdue due item(s) with {{ $overdueBalance }} outstanding.</span>
            </span>
        </a>
        <a class="workflow-card" href="{{ route('payment-reminders.index', ['status' => \App\Models\PaymentReminder::STATUS_PENDING]) }}">
            <span>
                <span class="workflow-card-title">Pending reminder records</span>
                <span class="workflow-card-text">{{ $pendingReminderCount }} internal reminder record(s) still pending.</span>
            </span>
        </a>
        <a class="workflow-card" href="{{ route('promotion-batches.index') }}">
            <span>
                <span class="workflow-card-title">Draft promotion batches</span>
                <span class="workflow-card-text">{{ $draftBatchCount }} batch(es) remain in draft before confirmation.</span>
            </span>
        </a>
    </div>

    <h2 class="section-heading">Quick actions</h2>
    <div class="card-grid">
        <a class="workflow-card" href="{{ route('families.create') }}">
            <span>
                <span class="workflow-card-title">Register student</span>
                <span class="workflow-card-text">Create a family first, then register its student record.</span>
            </span>
        </a>
        <a class="workflow-card" href="{{ route('students.index') }}">
            <span>
                <span class="workflow-card-title">Student directory</span>
                <span class="workflow-card-text">Search students, academic placement, families, and linked guardians.</span>
            </span>
        </a>
        <a class="workflow-card" href="{{ route('families.index') }}">
            <span>
                <span class="workflow-card-title">Families</span>
                <span class="workflow-card-text">Review households, guardians, and registration records.</span>
            </span>
        </a>
        <a class="workflow-card" href="{{ route('payments.collect') }}">
            <span>
                <span class="workflow-card-title">Collect payment</span>
                <span class="workflow-card-text">Find a family and record a manual payment allocation.</span>
            </span>
        </a>
        <a class="workflow-card" href="{{ route('fee-categories.index') }}">
            <span>
                <span class="workflow-card-title">Fee management</span>
                <span class="workflow-card-text">Manage fee categories and year-specific price structures.</span>
            </span>
        </a>
        <a class="workflow-card" href="{{ route('academic-years.index') }}">
            <span>
                <span class="workflow-card-title">Academic setup</span>
                <span class="workflow-card-text">Manage academic years, grades, sections, and school settings.</span>
            </span>
        </a>
        <a class="workflow-card" href="{{ route('dues-dashboard.index') }}">
            <span>
                <span class="workflow-card-title">Reports</span>
                <span class="workflow-card-text">Review stored due, collected, and outstanding balance snapshots.</span>
            </span>
        </a>
    </div>

    <h2 class="section-heading">Recent activity</h2>
    <x-card>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Occurred at</th><th>Action</th><th>Actor</th><th>Record</th></tr></thead>
                <tbody>
                @forelse ($recentAuditLogs as $auditLog)
                    <tr>
                        <td>{{ $auditLog->occurred_at->toDateTimeString() }}</td>
                        <td>{{ str($auditLog->action)->headline() }}</td>
                        <td>{{ $auditLog->actor?->name ?? 'System' }}</td>
                        <td>{{ $auditLog->auditable_type ? class_basename($auditLog->auditable_type).' #'.$auditLog->auditable_id : '—' }}</td>
                    </tr>
                @empty
                    <tr class="table-empty"><td colspan="4">No audit activity has been recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="page-actions"><x-button-link :href="route('audit-logs.index')" variant="secondary">View audit log</x-button-link></div>
    </x-card>
@endsection
