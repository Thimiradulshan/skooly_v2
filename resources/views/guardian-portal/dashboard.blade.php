@extends('layouts.app')
@section('title', 'Guardian portal')
@section('content')
<x-page-header title="Welcome, {{ $guardian->name }}" subtitle="Only students explicitly linked to you are shown." eyebrow="Guardian portal" />
<div class="page-actions"><x-button-link :href="route('guardian.dues')">Fees &amp; dues</x-button-link><x-button-link :href="route('guardian.payments')" variant="quiet">Payment history</x-button-link></div>
<x-card title="Your students"><div class="table-wrap"><table class="table"><thead><tr><th>Student</th><th>Profile</th></tr></thead><tbody>@forelse($students as $student)<tr><td>{{ $student->name }}</td><td><x-button-link :href="route('guardian.students.show', $student)" variant="quiet" size="small">View profile</x-button-link></td></tr>@empty<tr class="table-empty"><td colspan="2">No students are currently linked to this Guardian account.</td></tr>@endforelse</tbody></table></div></x-card>
<x-card title="Outstanding fees"><div class="table-wrap"><table class="table"><thead><tr><th>Student</th><th>Description</th><th>Due date</th><th class="num">Balance</th></tr></thead><tbody>@forelse($dueItems as $dueItem)<tr><td>{{ $dueItem->student->name }}</td><td>{{ $dueItem->description }}</td><td>{{ $dueItem->due_date?->toDateString() }}</td><td class="num">{{ $dueItem->balance_amount }}</td></tr>@empty<tr class="table-empty"><td colspan="4">No outstanding fees are available for your linked students.</td></tr>@endforelse</tbody></table></div></x-card>
@endsection
