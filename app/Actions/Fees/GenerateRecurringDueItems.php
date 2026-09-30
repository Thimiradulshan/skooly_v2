<?php

namespace App\Actions\Fees;

use App\Actions\Audit\RecordAuditLog;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\StudentDueItem;
use App\Models\StudentFeeSubscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GenerateRecurringDueItems
{
    /**
     * Generate recurring StudentDueItems for a cycle from the academic year fee structures.
     *
     * @return int Number of due items created in this run.
     */
    public function handle(
        AcademicYear $academicYear,
        string $dueDate,
        ?string $cycleKey = null,
        ?User $actor = null,
    ): int {
        $cycleKey ??= substr($dueDate, 0, 7);

        return DB::transaction(function () use ($academicYear, $dueDate, $cycleKey, $actor): int {
            $created = 0;

            $feeStructures = FeeStructure::query()
                ->where('academic_year_id', $academicYear->id)
                ->whereHas('feeCategory', fn ($query) => $query->where('is_recurring', true))
                ->with('feeCategory')
                ->get();

            foreach ($feeStructures as $feeStructure) {
                $feeCategory = $feeStructure->feeCategory;

                $studentIds = Enrollment::query()
                    ->where('academic_year_id', $academicYear->id)
                    ->where('grade_id', $feeStructure->grade_id)
                    ->pluck('student_id');

                foreach ($studentIds as $studentId) {
                    if ($feeCategory->is_opt_in && ! $this->hasActiveSubscription($studentId, $feeCategory->id, $academicYear->id)) {
                        continue;
                    }

                    $generationKey = implode('|', [
                        'student:'.$studentId,
                        'category:'.$feeCategory->id,
                        'structure:'.$feeStructure->id,
                        'cycle:'.$cycleKey,
                    ]);

                    if (StudentDueItem::query()->where('generation_key', $generationKey)->exists()) {
                        continue;
                    }

                    $originalAmount = self::toCents($feeStructure->amount);
                    $discountAmount = $this->applyDiscounts($studentId, $feeCategory->id, $originalAmount, $dueDate);
                    $netAmount = $originalAmount - $discountAmount;

                    $dueItem = StudentDueItem::query()->create([
                        'student_id' => $studentId,
                        'academic_year_id' => $academicYear->id,
                        'fee_category_id' => $feeCategory->id,
                        'fee_structure_id' => $feeStructure->id,
                        'description' => $feeCategory->name,
                        'frequency' => $feeStructure->frequency,
                        'original_amount' => self::fromCents($originalAmount),
                        'discount_amount' => self::fromCents($discountAmount),
                        'net_amount' => self::fromCents($netAmount),
                        'paid_amount' => 0,
                        'balance_amount' => self::fromCents($netAmount),
                        'due_date' => $dueDate,
                        'status' => StudentDueItem::STATUS_UNPAID,
                        'generation_key' => $generationKey,
                    ]);

                    $this->recordDiscountSnapshots($studentId, $feeCategory->id, $dueItem, $originalAmount, $dueDate);

                    $created++;
                }
            }

            (new RecordAuditLog)->handle(AuditLog::ACTION_RECURRING_DUES_GENERATED, $academicYear, $actor, [
                'academic_year_id' => $academicYear->id,
                'due_date' => $dueDate,
                'cycle_key' => $cycleKey,
                'created_count' => $created,
            ]);

            return $created;
        });
    }

    private function hasActiveSubscription(int $studentId, int $feeCategoryId, int $academicYearId): bool
    {
        return StudentFeeSubscription::query()
            ->where('student_id', $studentId)
            ->where('fee_category_id', $feeCategoryId)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * @return int Total discount in cents, clamped so it never exceeds the original amount.
     */
    private function applyDiscounts(int $studentId, int $feeCategoryId, int $originalAmount, string $dueDate): int
    {
        $remaining = $originalAmount;

        foreach ($this->applicableDiscounts($studentId, $feeCategoryId, $dueDate) as $discount) {
            $applied = $this->discountAmountInCents($discount, $originalAmount);
            $applied = min($applied, $remaining);

            if ($applied > 0) {
                $remaining -= $applied;
            }
        }

        return $originalAmount - $remaining;
    }

    private function recordDiscountSnapshots(
        int $studentId,
        int $feeCategoryId,
        StudentDueItem $dueItem,
        int $originalAmount,
        string $dueDate,
    ): void {
        $remaining = $originalAmount;

        foreach ($this->applicableDiscounts($studentId, $feeCategoryId, $dueDate) as $discount) {
            $applied = min($this->discountAmountInCents($discount, $originalAmount), $remaining);

            if ($applied <= 0) {
                continue;
            }

            $dueItem->dueItemDiscounts()->create([
                'discount_id' => $discount->id,
                'type' => $discount->type,
                'value' => self::fromCents(self::toCents($discount->value)),
                'value_type' => $discount->value_type,
                'amount_applied' => self::fromCents($applied),
            ]);

            $remaining -= $applied;
        }
    }

    /**
     * @return Collection<int, Discount>
     */
    private function applicableDiscounts(int $studentId, int $feeCategoryId, string $dueDate)
    {
        return Discount::query()
            ->where('student_id', $studentId)
            ->where('applies_to_fee_category_id', $feeCategoryId)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_on')->orWhere('starts_on', '<=', $dueDate))
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhere('ends_on', '>=', $dueDate))
            ->get();
    }

    private function discountAmountInCents(Discount $discount, int $originalAmount): int
    {
        if ($discount->value_type === 'percentage') {
            return (int) round($originalAmount * (float) $discount->value / 100);
        }

        return self::toCents($discount->value);
    }

    private static function toCents(string|int|float $amount): int
    {
        if (is_int($amount)) {
            return $amount * 100;
        }

        if (is_float($amount)) {
            $amount = number_format($amount, 2, '.', '');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private static function fromCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
