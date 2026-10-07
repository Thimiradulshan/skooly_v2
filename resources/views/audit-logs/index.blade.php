@extends('layouts.app')

@section('title', 'Audit log')

@section('content')
    <x-page-header title="Audit log"
                    subtitle="Read-only record of sensitive workflow activity."
                    eyebrow="Governance" />

    <div class="page-actions">
        <x-button-link :href="route('audit-logs.export.csv', request()->except('page'))" variant="secondary">Export CSV</x-button-link>
        <x-button-link :href="route('audit-logs.export.pdf', request()->except('page'))" variant="secondary">Export PDF</x-button-link>
    </div>

    <x-card title="Filters">
        <form method="GET" action="{{ route('audit-logs.index') }}">
            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="action">Action</label>
                    <select class="form-control" id="action" name="action">
                        <option value="">-- all --</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ str($action)->headline() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label class="form-label" for="actor_user_id">Actor</label>
                    <select class="form-control" id="actor_user_id" name="actor_user_id">
                        <option value="">-- all --</option>
                        @foreach ($actors as $actor)
                            <option value="{{ $actor->id }}" {{ (int) request('actor_user_id') === $actor->id ? 'selected' : '' }}>{{ $actor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label class="form-label" for="auditable_type">Record type</label>
                    <select class="form-control" id="auditable_type" name="auditable_type">
                        <option value="">-- all --</option>
                        @foreach ($auditableTypes as $auditableType)
                            <option value="{{ $auditableType }}" {{ request('auditable_type') === $auditableType ? 'selected' : '' }}>{{ class_basename($auditableType) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label class="form-label" for="occurred_at_from">Occurred from</label>
                    <input class="form-control" type="date" id="occurred_at_from" name="occurred_at_from" value="{{ request('occurred_at_from') }}">
                </div>
                <div class="form-field">
                    <label class="form-label" for="occurred_at_to">Occurred to</label>
                    <input class="form-control" type="date" id="occurred_at_to" name="occurred_at_to" value="{{ request('occurred_at_to') }}">
                </div>
            </div>
            <div class="btn-row"><button type="submit" class="btn">Apply filters</button></div>
        </form>
    </x-card>

    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>Occurred at</th><th>Action</th><th>Actor</th><th>Record</th><th class="actions">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($auditLogs as $auditLog)
                <tr>
                    <td>{{ $auditLog->occurred_at->toDateTimeString() }}</td>
                    <td>{{ str($auditLog->action)->headline() }}</td>
                    <td>{{ $auditLog->actor?->name ?? 'System' }}</td>
                    <td>
                        @if ($auditLog->auditable_type)
                            {{ class_basename($auditLog->auditable_type) }} #{{ $auditLog->auditable_id }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="actions">
                        <x-button-link :href="route('audit-logs.show', $auditLog)" variant="quiet" size="small">View</x-button-link>
                    </td>
                </tr>
            @empty
                <tr class="table-empty">
                    <td colspan="5">
                        <span class="empty-state-title">No audit records yet</span>
                        Sensitive workflow activity will appear here when it is recorded.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <x-pagination :paginator="$auditLogs" />
    <p class="note">Audit records are retained forever, append-only, and cannot be changed or deleted.</p>
@endsection
