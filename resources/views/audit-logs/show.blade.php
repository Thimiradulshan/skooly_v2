@extends('layouts.app')

@section('title', 'Audit log '.$auditLog->id)

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('audit-logs.index') }}">Audit log</a>
        <span class="breadcrumb-sep">/</span>
        <span>Entry {{ $auditLog->id }}</span>
    </div>

    <x-page-header :title="'Audit entry '.$auditLog->id"
                   subtitle="Stored record of a completed workflow action."
                   eyebrow="Governance" />

    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('audit-logs.index') }}">Back to audit log</a>
    </div>

    <x-card title="Audit entry">
        <dl class="kv">
            <div class="kv-row"><dt>Occurred at</dt><dd>{{ $auditLog->occurred_at->toDateTimeString() }}</dd></div>
            <div class="kv-row"><dt>Action</dt><dd>{{ str($auditLog->action)->headline() }}</dd></div>
            <div class="kv-row"><dt>Actor</dt><dd>{{ $auditLog->actor?->name ?? 'System' }}</dd></div>
            <div class="kv-row"><dt>Record</dt><dd>{{ $auditLog->auditable_type ? class_basename($auditLog->auditable_type).' #'.$auditLog->auditable_id : '—' }}</dd></div>
        </dl>
    </x-card>

    <x-card title="Stored metadata">
        <pre>{{ json_encode($auditLog->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </x-card>

    <p class="note">This screen displays the stored audit snapshot only. Audit records cannot be changed or deleted.</p>
@endsection
