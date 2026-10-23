<?php

namespace App\Actions\Notifications;

use App\Academic\ActiveAcademicYear;
use App\Models\PaymentReminder;
use App\Models\StudentDueItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class PaymentReminderEmailDeliveryEligibility
{
    /**
     * Determine whether a stored reminder can safely be delivered by email.
     *
     * This is read-only and intentionally requires every due item in a combined
     * reminder to be visible to the Guardian and currently outstanding.
     */
    public function handle(PaymentReminder $paymentReminder): bool
    {
        if ($paymentReminder->status !== PaymentReminder::STATUS_PENDING) {
            return false;
        }

        $guardian = $paymentReminder->guardian()->first();

        if ($guardian === null
            || $guardian->family_id !== $paymentReminder->family_id
            || $guardian->email === null
            || trim($guardian->email) === ''
            || $guardian->payment_reminder_email_opt_in_at === null
            || $guardian->payment_reminder_email_verified_at === null
            || $guardian->payment_reminder_email_opt_out_at !== null) {
            return false;
        }

        try {
            $activeAcademicYear = (new ActiveAcademicYear)->current();
        } catch (InvalidArgumentException|ModelNotFoundException) {
            return false;
        }

        $dueItemIds = $this->dueItemIds($paymentReminder);

        if ($dueItemIds === []) {
            return false;
        }

        $dueItems = StudentDueItem::query()
            ->with('student')
            ->whereKey($dueItemIds)
            ->get();

        if ($dueItems->count() !== count($dueItemIds)) {
            return false;
        }

        $guardianStudentIds = $guardian->students()->pluck('students.id')->all();

        foreach ($dueItems as $dueItem) {
            if ($dueItem->academic_year_id !== $activeAcademicYear->id
                || $dueItem->student->family_id !== $guardian->family_id
                || $dueItem->student->family_id !== $paymentReminder->family_id
                || ! in_array($dueItem->student_id, $guardianStudentIds, true)
                || (float) $dueItem->balance_amount <= 0
                || ! in_array($dueItem->status, [StudentDueItem::STATUS_UNPAID, StudentDueItem::STATUS_PARTIALLY_PAID], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, int>
     */
    private function dueItemIds(PaymentReminder $paymentReminder): array
    {
        $dueItemIds = [];

        foreach ($paymentReminder->due_item_ids as $dueItemId) {
            if (is_int($dueItemId)) {
                $dueItemIds[] = $dueItemId;

                continue;
            }

            if (! ctype_digit($dueItemId)) {
                return [];
            }

            $dueItemIds[] = (int) $dueItemId;
        }

        return array_values(array_unique($dueItemIds));
    }
}
