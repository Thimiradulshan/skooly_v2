<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Family;
use App\Models\PaymentReminder;
use App\Models\PromotionBatch;
use App\Models\Student;
use App\Models\StudentDueItem;

class AdminDashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'familyCount' => Family::query()->count(),
            'studentCount' => Student::query()->count(),
            'outstandingCount' => StudentDueItem::query()->where('balance_amount', '>', 0)->count(),
            'draftBatchCount' => PromotionBatch::query()->where('status', PromotionBatch::STATUS_DRAFT)->count(),
            'reminderCount' => PaymentReminder::query()->count(),
            'recentAuditLogs' => AuditLog::query()
                ->with('actor')
                ->latest('occurred_at')
                ->latest('id')
                ->limit(8)
                ->get(),
        ]);
    }
}
