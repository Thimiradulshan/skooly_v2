<?php

namespace App\Actions\Discounts;

use App\Actions\Audit\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\Discount;
use App\Models\FeeCategory;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApplyStudentDiscount
{
    /**
     * Create a Discount for one Student and one FeeCategory.
     *
     * Existing StudentDueItems are never modified; discounts are snapshotted at due generation.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(
        Student $student,
        FeeCategory $feeCategory,
        array $data,
        ?User $actor = null,
    ): Discount {
        return DB::transaction(function () use ($student, $feeCategory, $data, $actor): Discount {
            $discount = Discount::query()->create([
                'student_id' => $student->id,
                'applies_to_fee_category_id' => $feeCategory->id,
                'type' => $data['type'],
                'value' => $data['value'],
                'value_type' => $data['value_type'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'starts_on' => $data['starts_on'] ?? null,
                'ends_on' => $data['ends_on'] ?? null,
            ]);

            (new RecordAuditLog)->handle(AuditLog::ACTION_DISCOUNT_APPLIED, $discount, $actor, [
                'discount_id' => $discount->id,
                'student_id' => $student->id,
                'fee_category_id' => $feeCategory->id,
                'type' => $discount->type,
                'value' => $discount->value,
                'value_type' => $discount->value_type,
            ]);

            return $discount;
        });
    }
}
