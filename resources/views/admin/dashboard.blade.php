@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <x-page-header title="Operations overview"
                   subtitle="Start a workflow or review what needs attention across the school."
                   eyebrow="Today" />

    <div class="card-grid" data-testid="dashboard-metrics">
        <x-stat-card label="Families" :value="$familyCount" hint="Registered households" />
        <x-stat-card label="Students" :value="$studentCount" hint="Student profiles" />
        <x-stat-card label="Outstanding due items" :value="$outstandingCount" hint="Require collection" />
        <x-stat-card label="Draft promotion batches" :value="$draftBatchCount" hint="Awaiting confirmation" />
        <x-stat-card label="Payment reminders" :value="$reminderCount" hint="Internal outbox records" />
    </div>

    <h2 class="section-heading">Academic setup</h2>
    <div class="card-grid">
        <a class="workflow-card" href="{{ route('academic-years.index') }}">
            <span>
                <span class="workflow-card-title">Academic setup</span>
                <span class="workflow-card-text">Manage academic years, terms, grades, sections, and the active school year.</span>
            </span>
        </a>
    </div>

    <h2 class="section-heading">Registration</h2>
    <div class="card-grid">
        <a class="workflow-card" href="{{ route('families.index') }}">
            <span>
                <span class="workflow-card-title">Families &amp; students</span>
                <span class="workflow-card-text">Review households, guardians, and enrolled students.</span>
            </span>
        </a>
        <a class="workflow-card" href="{{ route('families.create') }}">
            <span>
                <span class="workflow-card-title">Create family</span>
                <span class="workflow-card-text">Start a household record and add its first guardian.</span>
            </span>
        </a>
    </div>

    <h2 class="section-heading">Fees &amp; dues</h2>
    <div class="card-grid">
        <a class="workflow-card" href="{{ route('fee-categories.index') }}">
            <span>
                <span class="workflow-card-title">Fee setup</span>
                <span class="workflow-card-text">Manage categories and year-specific fee structures.</span>
            </span>
        </a>
        <a class="workflow-card" href="{{ route('due-generation.recurring.create') }}">
            <span>
                <span class="workflow-card-title">Generate recurring dues</span>
                <span class="workflow-card-text">Turn active fee structures into payable student items.</span>
            </span>
        </a>
        <a class="workflow-card" href="{{ route('dues-dashboard.index') }}">
            <span>
                <span class="workflow-card-title">Dues dashboard</span>
                <span class="workflow-card-text">Review due, collected, and outstanding balances.</span>
            </span>
        </a>
    </div>

    <h2 class="section-heading">Payments</h2>
    <div class="card-grid">
        <a class="workflow-card" href="{{ route('families.index') }}">
            <span>
                <span class="workflow-card-title">Record a payment</span>
                <span class="workflow-card-text">Open a family and allocate a payment manually.</span>
            </span>
        </a>
    </div>

    <h2 class="section-heading">Events &amp; promotion</h2>
    <div class="card-grid">
        <a class="workflow-card" href="{{ route('events.index') }}">
            <span>
                <span class="workflow-card-title">Events</span>
                <span class="workflow-card-text">Create events, charges, and participation records.</span>
            </span>
        </a>
        <a class="workflow-card" href="{{ route('due-generation.events.create') }}">
            <span>
                <span class="workflow-card-title">Generate event dues</span>
                <span class="workflow-card-text">Charge applicable students for an existing event.</span>
            </span>
        </a>
        <a class="workflow-card" href="{{ route('promotion-batches.index') }}">
            <span>
                <span class="workflow-card-title">Promotion</span>
                <span class="workflow-card-text">Review and confirm year-to-year student movement.</span>
            </span>
        </a>
    </div>

    <h2 class="section-heading">Reminders</h2>
    <div class="card-grid">
        <a class="workflow-card" href="{{ route('payment-reminders.index') }}">
            <span>
                <span class="workflow-card-title">Payment reminders</span>
                <span class="workflow-card-text">Generate and preview the internal reminder outbox.</span>
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
