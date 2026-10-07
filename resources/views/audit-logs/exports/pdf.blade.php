<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Audit log export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        h2 { font-size: 12px; margin: 18px 0 6px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #d1d5db; padding: 5px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .filters { width: auto; }
        .filters th { width: 110px; }
        .metadata { max-width: 240px; overflow-wrap: break-word; }
    </style>
</head>
<body>
    <h1>Audit log export</h1>
    <p>Stored audit records only. Generated {{ now()->toDateTimeString() }}.</p>

    <h2>Filters</h2>
    <table class="filters">
        @foreach ($filters as $label => $value)
            <tr><th>{{ $label }}</th><td>{{ $value }}</td></tr>
        @endforeach
    </table>

    <h2>Audit records</h2>
    <table>
        <thead>
        <tr><th>ID</th><th>Occurred at</th><th>Action</th><th>Actor</th><th>Record</th><th>Metadata</th></tr>
        </thead>
        <tbody>
        @forelse ($auditLogs as $auditLog)
            <tr>
                <td>{{ $auditLog->id }}</td>
                <td>{{ $auditLog->occurred_at->toDateTimeString() }}</td>
                <td>{{ str($auditLog->action)->headline() }}</td>
                <td>{{ $auditLog->actor?->name ?? 'System' }}</td>
                <td>{{ $auditLog->auditable_type ? class_basename($auditLog->auditable_type).' #'.$auditLog->auditable_id : '—' }}</td>
                <td class="metadata">{{ json_encode($auditLog->metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No audit records match these filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
