<?php

namespace App\Actions\Reports;

use App\Models\StudentDueItem;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class BuildDuesDashboardReport
{
    /**
     * Build a read-only dues dashboard report from stored StudentDueItem snapshots.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function handle(array $filters = []): array
    {
        $this->guardYearScopedFilters($filters);

        return [
            'summary' => $this->summary($filters),
            'by_fee_category' => $this->byFeeCategory($filters),
            'family_balances' => $this->familyBalances($filters),
            'student_balances' => $this->studentBalances($filters),
            'outstanding_due_items' => $this->outstandingDueItems($filters),
        ];
    }

    /**
     * Grade and section are year-specific through Enrollment, so they need an academic year.
     *
     * @param  array<string, mixed>  $filters
     */
    private function guardYearScopedFilters(array $filters): void
    {
        if (empty($filters['academic_year_id']) && ! empty($filters['grade_id'])) {
            throw new InvalidArgumentException('grade_id requires academic_year_id because enrollment is year-specific.');
        }

        if (empty($filters['academic_year_id']) && ! empty($filters['section_id'])) {
            throw new InvalidArgumentException('section_id requires academic_year_id because enrollment is year-specific.');
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        $query = StudentDueItem::query();

        if (! empty($filters['academic_year_id'])) {
            $query->where('student_due_items.academic_year_id', $filters['academic_year_id']);
        }

        if (! empty($filters['fee_category_id'])) {
            $query->where('student_due_items.fee_category_id', $filters['fee_category_id']);
        }

        if (! empty($filters['family_id'])) {
            $query->whereHas('student', fn ($student) => $student->where('students.family_id', $filters['family_id']));
        }

        if (! empty($filters['due_date_from'])) {
            $query->whereDate('student_due_items.due_date', '>=', $filters['due_date_from']);
        }

        if (! empty($filters['due_date_to'])) {
            $query->whereDate('student_due_items.due_date', '<=', $filters['due_date_to']);
        }

        if (! empty($filters['grade_id']) || ! empty($filters['section_id'])) {
            $query->whereHas('student.enrollments', function (Builder $enrollment) use ($filters): void {
                $enrollment->where('academic_year_id', $filters['academic_year_id']);

                if (! empty($filters['grade_id'])) {
                    $enrollment->where('grade_id', $filters['grade_id']);
                }

                if (! empty($filters['section_id'])) {
                    $enrollment->where('section_id', $filters['section_id']);
                }
            });
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function summary(array $filters): array
    {
        $row = $this->baseQuery($filters)
            ->toBase()
            ->selectRaw('
                COALESCE(SUM(original_amount), 0) AS total_original_amount,
                COALESCE(SUM(discount_amount), 0) AS total_discount_amount,
                COALESCE(SUM(net_amount), 0) AS total_net_amount,
                COALESCE(SUM(paid_amount), 0) AS total_paid_amount,
                COALESCE(SUM(balance_amount), 0) AS total_balance_amount,
                COUNT(*) AS due_item_count,
                COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS unpaid_count,
                COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS partially_paid_count,
                COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS paid_count
            ', [
                StudentDueItem::STATUS_UNPAID,
                StudentDueItem::STATUS_PARTIALLY_PAID,
                StudentDueItem::STATUS_PAID,
            ])
            ->first();

        return [
            'total_original_amount' => $this->money($row->total_original_amount ?? '0'),
            'total_discount_amount' => $this->money($row->total_discount_amount ?? '0'),
            'total_net_amount' => $this->money($row->total_net_amount ?? '0'),
            'total_paid_amount' => $this->money($row->total_paid_amount ?? '0'),
            'total_balance_amount' => $this->money($row->total_balance_amount ?? '0'),
            'due_item_count' => (int) ($row->due_item_count ?? 0),
            'unpaid_count' => (int) ($row->unpaid_count ?? 0),
            'partially_paid_count' => (int) ($row->partially_paid_count ?? 0),
            'paid_count' => (int) ($row->paid_count ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function byFeeCategory(array $filters): array
    {
        $rows = $this->baseQuery($filters)
            ->toBase()
            ->join('fee_categories', 'fee_categories.id', '=', 'student_due_items.fee_category_id')
            ->groupBy('fee_categories.id', 'fee_categories.name')
            ->orderBy('fee_categories.id')
            ->selectRaw('
                fee_categories.id AS fee_category_id,
                fee_categories.name AS fee_category_name,
                COALESCE(SUM(student_due_items.net_amount), 0) AS total_net_amount,
                COALESCE(SUM(student_due_items.paid_amount), 0) AS total_paid_amount,
                COALESCE(SUM(student_due_items.balance_amount), 0) AS total_balance_amount,
                COUNT(*) AS due_item_count
            ')
            ->get();

        return $rows->map(fn ($row): array => [
            'fee_category_id' => (int) $row->fee_category_id,
            'fee_category_name' => $row->fee_category_name,
            'total_net_amount' => $this->money($row->total_net_amount),
            'total_paid_amount' => $this->money($row->total_paid_amount),
            'total_balance_amount' => $this->money($row->total_balance_amount),
            'due_item_count' => (int) $row->due_item_count,
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function familyBalances(array $filters): array
    {
        $rows = $this->baseQuery($filters)
            ->toBase()
            ->join('students', 'students.id', '=', 'student_due_items.student_id')
            ->join('families', 'families.id', '=', 'students.family_id')
            ->groupBy('families.id', 'families.family_code')
            ->orderByDesc('total_balance_amount')
            ->selectRaw('
                families.id AS family_id,
                families.family_code,
                COALESCE(SUM(student_due_items.net_amount), 0) AS total_net_amount,
                COALESCE(SUM(student_due_items.paid_amount), 0) AS total_paid_amount,
                COALESCE(SUM(student_due_items.balance_amount), 0) AS total_balance_amount,
                COUNT(*) AS due_item_count
            ')
            ->get();

        return $rows->map(fn ($row): array => [
            'family_id' => (int) $row->family_id,
            'family_code' => $row->family_code,
            'total_net_amount' => $this->money($row->total_net_amount),
            'total_paid_amount' => $this->money($row->total_paid_amount),
            'total_balance_amount' => $this->money($row->total_balance_amount),
            'due_item_count' => (int) $row->due_item_count,
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function studentBalances(array $filters): array
    {
        $rows = $this->baseQuery($filters)
            ->toBase()
            ->join('students', 'students.id', '=', 'student_due_items.student_id')
            ->join('families', 'families.id', '=', 'students.family_id')
            ->groupBy(
                'students.id',
                'students.name',
                'students.admission_no',
                'families.id',
                'families.family_code'
            )
            ->orderByDesc('total_balance_amount')
            ->selectRaw('
                students.id AS student_id,
                students.name AS student_name,
                students.admission_no,
                families.id AS family_id,
                families.family_code,
                COALESCE(SUM(student_due_items.net_amount), 0) AS total_net_amount,
                COALESCE(SUM(student_due_items.paid_amount), 0) AS total_paid_amount,
                COALESCE(SUM(student_due_items.balance_amount), 0) AS total_balance_amount,
                COUNT(*) AS due_item_count
            ')
            ->get();

        return $rows->map(fn ($row): array => [
            'student_id' => (int) $row->student_id,
            'student_name' => $row->student_name,
            'admission_no' => $row->admission_no,
            'family_id' => (int) $row->family_id,
            'family_code' => $row->family_code,
            'total_net_amount' => $this->money($row->total_net_amount),
            'total_paid_amount' => $this->money($row->total_paid_amount),
            'total_balance_amount' => $this->money($row->total_balance_amount),
            'due_item_count' => (int) $row->due_item_count,
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function outstandingDueItems(array $filters): array
    {
        $rows = $this->baseQuery($filters)
            ->toBase()
            ->where('student_due_items.balance_amount', '>', 0)
            ->join('students', 'students.id', '=', 'student_due_items.student_id')
            ->join('families', 'families.id', '=', 'students.family_id')
            ->join('fee_categories', 'fee_categories.id', '=', 'student_due_items.fee_category_id')
            ->orderBy('student_due_items.due_date')
            ->orderBy('student_due_items.id')
            ->selectRaw('
                student_due_items.id AS student_due_item_id,
                students.id AS student_id,
                students.name AS student_name,
                students.admission_no,
                families.id AS family_id,
                families.family_code,
                fee_categories.id AS fee_category_id,
                fee_categories.name AS fee_category_name,
                student_due_items.description,
                student_due_items.due_date,
                student_due_items.net_amount,
                student_due_items.paid_amount,
                student_due_items.balance_amount,
                student_due_items.status
            ')
            ->get();

        return $rows->map(fn ($row): array => [
            'student_due_item_id' => (int) $row->student_due_item_id,
            'student_id' => (int) $row->student_id,
            'student_name' => $row->student_name,
            'admission_no' => $row->admission_no,
            'family_id' => (int) $row->family_id,
            'family_code' => $row->family_code,
            'fee_category_id' => (int) $row->fee_category_id,
            'fee_category_name' => $row->fee_category_name,
            'description' => $row->description,
            'due_date' => $row->due_date,
            'net_amount' => $this->money($row->net_amount),
            'paid_amount' => $this->money($row->paid_amount),
            'balance_amount' => $this->money($row->balance_amount),
            'status' => $row->status,
        ])->all();
    }

    /**
     * Normalize a decimal value to two places without floating-point arithmetic.
     */
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
