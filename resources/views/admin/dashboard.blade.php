@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="dashboard-hero">
        <div>
            <p class="dashboard-hero-eyebrow">{{ $currentAcademicYear?->name ?? 'Not configured' }}</p>
            <h1>Dashboard</h1>
            <p>Welcome back. Here is the current operational view of Skooly.</p>
        </div>
        <x-button-link :href="route('payments.collect')" class="dashboard-payment-action">Collect payment</x-button-link>
    </section>

    <section aria-labelledby="school-overview-heading">
        <div class="dashboard-section-heading">
            <div><p class="section-kicker">Overview</p><h2 id="school-overview-heading">School at a glance</h2></div>
            <p>{{ $currentAcademicYear ? 'Current academic year: '.$currentAcademicYear->name : 'Not configured — set an active academic year to view year-scoped balances.' }}</p>
        </div>
        <div class="ui-kpi-grid" data-testid="dashboard-metrics">
            <x-ui.kpi-card title="Registered students" :value="$studentCount" hint="All student profiles" icon="users" accent="primary" />
            <x-ui.kpi-card title="Families" :value="$familyCount" hint="Registered households" icon="family" accent="success" />
            <x-ui.kpi-card title="Active teachers" :value="$teacherCount" hint="Teacher accounts enabled" icon="staff" accent="warning" />
            <x-ui.kpi-card title="Outstanding dues" :value="$outstandingBalance" :hint="'Outstanding fees for '.$studentsWithOutstandingBalance.' student(s)'" icon="dues" accent="surface" />
        </div>
    </section>

    <section class="dashboard-secondary-grid" aria-label="Financial and operational indicators">
        <x-stat-card label="Overdue balances" :value="$overdueBalance" :hint="$overdueDueItemCount.' overdue due item(s)'" />
        <x-stat-card label="Collected this month" :value="$paymentsCollectedThisMonth" hint="Recorded payments by paid date" />
        <x-stat-card label="Active students" :value="$activeStudentCount" hint="Current student status" />
    </section>

    <section class="dashboard-transactions" aria-labelledby="recent-transactions-heading">
        <x-card>
            <div class="dashboard-card-heading">
                <div><p class="section-kicker">Finance</p><h2 id="recent-transactions-heading">Recent transactions</h2></div>
                <x-button-link :href="route('payments.index')" variant="quiet">View all payments</x-button-link>
            </div>
            <div class="table-wrap">
                <table class="table table--transactions">
                    <thead><tr><th>Paid</th><th>Family</th><th>Reference</th><th>Receipt</th><th>Status</th><th class="num">Amount</th></tr></thead>
                    <tbody>
                    @forelse ($recentPayments as $payment)
                        <tr>
                            <td class="transaction-date">{{ $payment->paid_at->format('M j, Y') }}</td>
                            <td><a href="{{ route('families.show', $payment->family) }}">{{ $payment->family->family_code }}</a></td>
                            <td><a href="{{ route('payments.show', $payment) }}">{{ $payment->payment_reference ?: 'Payment '.$payment->id }}</a></td>
                            <td>
                                @if ($payment->receipt)
                                    <a href="{{ route('receipts.show', $payment->receipt) }}">{{ $payment->receipt->receipt_no }}</a>
                                @else
                                    Not issued
                                @endif
                            </td>
                            <td><x-status-badge :value="$payment->receipt ? 'paid' : 'pending'" /></td>
                            <td class="num">{{ $payment->amount }}</td>
                        </tr>
                    @empty
                        <tr class="table-empty">
                            <td colspan="6">
                                <span class="empty-state-title">No transactions yet</span>
                                Recorded payments will appear here once finance collects one.
                                <div class="empty-actions">
                                    <x-button-link :href="route('payments.collect')" variant="secondary" size="small">Collect payment</x-button-link>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </section>

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

    <section class="dashboard-activity" aria-labelledby="recent-activity-heading">
    <x-card>
        <div class="dashboard-card-heading">
            <div><p class="section-kicker">Audit trail</p><h2 id="recent-activity-heading">Recent activity</h2></div>
            <x-button-link :href="route('audit-logs.index')" variant="quiet">View audit log</x-button-link>
        </div>
        <div class="table-wrap">
            <table class="table table--activity">
                <thead><tr><th>Recorded</th><th>Activity</th><th>Performed by</th><th>Record</th></tr></thead>
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
    </x-card>
    </section>
@endsection
