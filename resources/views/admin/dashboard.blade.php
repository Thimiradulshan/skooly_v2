@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <x-page-header title="Admin dashboard"
                   subtitle="Everything the office needs, from registration through collection." />

    <div class="card-grid">
        <div class="metric">
            <span class="metric-label">Families</span>
            <span class="metric-value">{{ $familyCount }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Students</span>
            <span class="metric-value">{{ $studentCount }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Outstanding due items</span>
            <span class="metric-value">{{ $outstandingCount }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Draft promotion batches</span>
            <span class="metric-value">{{ $draftBatchCount }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Payment reminders</span>
            <span class="metric-value">{{ $reminderCount }}</span>
        </div>
    </div>

    <h2 class="section-heading">Registration</h2>
    <div class="card-grid">
        <a class="workflow-card" href="{{ route('families.index') }}">
            <span class="workflow-card-title">Families</span>
            <p class="workflow-card-text">Review households, guardians, and students.</p>
        </a>
        <a class="workflow-card" href="{{ route('families.create') }}">
            <span class="workflow-card-title">Create family</span>
            <p class="workflow-card-text">Start a new household with its guardians.</p>
        </a>
    </div>

    <h2 class="section-heading">Fees &amp; dues</h2>
    <div class="card-grid">
        <a class="workflow-card" href="{{ route('fee-categories.index') }}">
            <span class="workflow-card-title">Fee categories</span>
            <p class="workflow-card-text">Decide what can be charged and how it behaves.</p>
        </a>
        <a class="workflow-card" href="{{ route('fee-structures.index') }}">
            <span class="workflow-card-title">Fee structures</span>
            <p class="workflow-card-text">Set amounts per grade and academic year.</p>
        </a>
        <a class="workflow-card" href="{{ route('due-generation.recurring.create') }}">
            <span class="workflow-card-title">Recurring due generation</span>
            <p class="workflow-card-text">Turn fee structures into payable amounts.</p>
        </a>
        <a class="workflow-card" href="{{ route('dues-dashboard.index') }}">
            <span class="workflow-card-title">Dues dashboard</span>
            <p class="workflow-card-text">See what is due, collected, and outstanding.</p>
        </a>
    </div>

    <h2 class="section-heading">Payments</h2>
    <div class="card-grid">
        <a class="workflow-card" href="{{ route('families.index') }}">
            <span class="workflow-card-title">Record a payment</span>
            <p class="workflow-card-text">Open a family to settle its outstanding due items.</p>
        </a>
    </div>

    <h2 class="section-heading">Events &amp; promotion</h2>
    <div class="card-grid">
        <a class="workflow-card" href="{{ route('events.index') }}">
            <span class="workflow-card-title">Events</span>
            <p class="workflow-card-text">Create events, charges, and participation.</p>
        </a>
        <a class="workflow-card" href="{{ route('due-generation.events.create') }}">
            <span class="workflow-card-title">Event due generation</span>
            <p class="workflow-card-text">Charge the applicable students for an event.</p>
        </a>
        <a class="workflow-card" href="{{ route('promotion-batches.index') }}">
            <span class="workflow-card-title">Promotion</span>
            <p class="workflow-card-text">Move students into the next academic year.</p>
        </a>
    </div>

    <h2 class="section-heading">Reminders</h2>
    <div class="card-grid">
        <a class="workflow-card" href="{{ route('payment-reminders.index') }}">
            <span class="workflow-card-title">Payment reminders</span>
            <p class="workflow-card-text">Preview which guardians need telling about balances.</p>
        </a>
    </div>
@endsection
