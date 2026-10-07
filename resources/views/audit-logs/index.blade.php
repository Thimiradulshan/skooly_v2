@extends('layouts.app')

@section('title', 'Audit log')

@section('content')
    <x-page-header title="Audit log"
                   subtitle="Read-only record of sensitive workflow activity."
                   eyebrow="Governance" />

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

    <p class="note">Audit records are append-only and cannot be changed or deleted from this screen.</p>
@endsection
