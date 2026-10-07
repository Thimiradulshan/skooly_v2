<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index()
    {
        return view('audit-logs.index', [
            'auditLogs' => AuditLog::query()
                ->with('actor')
                ->latest('occurred_at')
                ->latest('id')
                ->get(),
        ]);
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load('actor');

        return view('audit-logs.show', ['auditLog' => $auditLog]);
    }
}
