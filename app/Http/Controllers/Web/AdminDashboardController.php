<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Family;
use App\Models\Payment;
use App\Models\PaymentReminder;
use App\Models\PaymentReversal;
use App\Models\PromotionBatch;
use App\Models\Role;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentDueItem;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $currentAcademicYear = SchoolSetting::query()->with('activeAcademicYear')->whereKey(1)->first()?->activeAcademicYear;
        $currentAcademicYearId = $currentAcademicYear?->id;
        $dueItems = StudentDueItem::query()->where('academic_year_id', $currentAcademicYearId);
        $financialSummary = (clone $dueItems)
            ->toBase()
            ->selectRaw('COALESCE(SUM(balance_amount), 0) AS outstanding_balance, COUNT(DISTINCT CASE WHEN balance_amount > 0 THEN student_id END) AS students_with_outstanding_balance')
            ->first();
        $overdueSummary = (clone $dueItems)
            ->where('balance_amount', '>', 0)
            ->whereDate('due_date', '<', today())
            ->toBase()
            ->selectRaw('COALESCE(SUM(balance_amount), 0) AS overdue_balance, COUNT(*) AS overdue_due_item_count')
            ->first();

        return view('admin.dashboard', [
            'currentAcademicYear' => $currentAcademicYear,
            'familyCount' => Family::query()->count(),
            'studentCount' => Student::query()->count(),
            'activeStudentCount' => Student::query()->where('status', Student::STATUS_ACTIVE)->count(),
            'teacherCount' => User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->where('name', Role::TEACHER))
                ->count(),
            'outstandingBalance' => $this->money($financialSummary->outstanding_balance ?? '0'),
            'overdueBalance' => $this->money($overdueSummary->overdue_balance ?? '0'),
            'studentsWithOutstandingBalance' => (int) ($financialSummary->students_with_outstanding_balance ?? 0),
            'overdueDueItemCount' => (int) ($overdueSummary->overdue_due_item_count ?? 0),
            'paymentsCollectedThisMonth' => $this->money(Payment::query()
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount')),
            'pendingReversalCount' => PaymentReversal::query()->where('status', PaymentReversal::STATUS_REQUESTED)->count(),
            'draftBatchCount' => PromotionBatch::query()->where('status', PromotionBatch::STATUS_DRAFT)->count(),
            'pendingReminderCount' => PaymentReminder::query()->where('status', PaymentReminder::STATUS_PENDING)->count(),
            'recentAuditLogs' => AuditLog::query()
                ->with('actor')
                ->latest('occurred_at')
                ->latest('id')
                ->limit(8)
                ->get(),
        ]);
    }

    private function money(mixed $value): string
    {
        $value = (string) ($value ?? '0');

        if (! str_contains($value, '.')) {
            return $value.'.00';
        }

        [$whole, $fraction] = explode('.', $value, 2);

        return $whole.'.'.str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
