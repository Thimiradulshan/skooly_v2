@extends('layouts.app')

@section('title', 'Admin dashboard')
@section('heading', 'Admin dashboard')

@section('content')
    <h2>Quick counts</h2>
    <table>
        <tbody>
        <tr><th>Families</th><td>{{ $familyCount }}</td></tr>
        <tr><th>Students</th><td>{{ $studentCount }}</td></tr>
        <tr><th>Outstanding due items</th><td>{{ $outstandingCount }}</td></tr>
        <tr><th>Draft promotion batches</th><td>{{ $draftBatchCount }}</td></tr>
        <tr><th>Payment reminders</th><td>{{ $reminderCount }}</td></tr>
        </tbody>
    </table>

    <h2>Workflows</h2>
    <div class="cards">
        <div class="card">
            <h3><a href="{{ route('families.index') }}">Families &amp; students</a></h3>
            <p>Register families, add students, and record payments per family.</p>
        </div>
        <div class="card">
            <h3><a href="{{ route('fee-categories.index') }}">Fee categories</a></h3>
            <p>Define what can be charged and whether it is recurring or opt-in.</p>
        </div>
        <div class="card">
            <h3><a href="{{ route('fee-structures.index') }}">Fee structures</a></h3>
            <p>Set the amount per grade and academic year.</p>
        </div>
        <div class="card">
            <h3><a href="{{ route('due-generation.recurring.create') }}">Recurring due generation</a></h3>
            <p>Generate recurring due items from fee structures.</p>
        </div>
        <div class="card">
            <h3><a href="{{ route('due-generation.events.create') }}">Event due generation</a></h3>
            <p>Generate due items for events you have already created.</p>
        </div>
        <div class="card">
            <h3><a href="{{ route('dues-dashboard.index') }}">Dues dashboard</a></h3>
            <p>Total due, collected, and outstanding balances with filters.</p>
        </div>
        <div class="card">
            <h3><a href="{{ route('payment-reminders.index') }}">Payment reminders</a></h3>
            <p>Generate and preview internal reminder records.</p>
        </div>
        <div class="card">
            <h3><a href="{{ route('events.index') }}">Events</a></h3>
            <p>Create events, add charges, and set participation.</p>
        </div>
        <div class="card">
            <h3><a href="{{ route('promotion-batches.index') }}">Promotion</a></h3>
            <p>Create and confirm promotion batches between academic years.</p>
        </div>
    </div>
@endsection
