<?php

namespace App\Actions\Events;

use App\Academic\ActiveAcademicYear;
use App\Actions\Audit\RecordAuditLog;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventCharge;
use App\Models\EventParticipation;
use App\Models\StudentDueItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GenerateEventDueItems
{
    /**
     * Generate StudentDueItems for an event and link them back through event_due_items.
     *
     * @return int Number of due items created in this run.
     */
    public function handle(Event $event, ?User $actor = null): int
    {
        (new ActiveAcademicYear)->ensure(AcademicYear::query()->findOrFail($event->academic_year_id));

        return DB::transaction(function () use ($event, $actor): int {
            if ($event->confirmed_at === null) {
                $event->update(['confirmed_at' => now()]);
            }

            $created = 0;

            foreach ($event->charges()->get() as $charge) {
                $studentIds = $this->applicableStudentIds($event, $charge);

                foreach ($studentIds as $studentId) {
                    $generationKey = implode(':', [
                        'event',
                        $event->id,
                        'student',
                        $studentId,
                        'charge',
                        $charge->id,
                    ]);

                    if (StudentDueItem::query()->where('generation_key', $generationKey)->exists()) {
                        continue;
                    }

                    $dueItem = $this->createDueItem($event, $charge, $studentId, $generationKey);

                    $event->eventDueItems()->create([
                        'student_due_item_id' => $dueItem->id,
                    ]);

                    $created++;
                }
            }

            (new RecordAuditLog)->handle(AuditLog::ACTION_EVENT_DUES_GENERATED, $event, $actor, [
                'event_id' => $event->id,
                'created_count' => $created,
            ]);

            return $created;
        });
    }

    /**
     * @return Collection<int, int>
     */
    private function applicableStudentIds(Event $event, EventCharge $charge): Collection
    {
        $studentIds = Enrollment::query()
            ->where('academic_year_id', $event->academic_year_id)
            ->where('grade_id', $charge->grade_id)
            ->pluck('student_id');

        if ($event->is_mandatory) {
            return $studentIds;
        }

        return $studentIds->filter(
            fn ($studentId) => EventParticipation::query()
                ->where('event_id', $event->id)
                ->where('student_id', $studentId)
                ->where('status', EventParticipation::STATUS_OPTED_IN)
                ->exists()
        )->values();
    }

    private function createDueItem(Event $event, EventCharge $charge, int $studentId, string $generationKey): StudentDueItem
    {
        $originalAmount = self::toCents($charge->amount);
        $discountAmount = $this->applyDiscounts($studentId, $event->fee_category_id, $originalAmount, $event->event_date);
        $netAmount = $originalAmount - $discountAmount;

        $dueItem = StudentDueItem::query()->create([
            'student_id' => $studentId,
            'academic_year_id' => $event->academic_year_id,
            'fee_category_id' => $event->fee_category_id,
            'fee_structure_id' => null,
            'description' => 'Event: '.$event->name,
            'frequency' => null,
            'original_amount' => self::fromCents($originalAmount),
            'discount_amount' => self::fromCents($discountAmount),
            'net_amount' => self::fromCents($netAmount),
            'paid_amount' => 0,
            'balance_amount' => self::fromCents($netAmount),
            'due_date' => $event->event_date,
            'status' => StudentDueItem::STATUS_UNPAID,
            'generation_key' => $generationKey,
        ]);

        $this->recordDiscountSnapshots($studentId, $event->fee_category_id, $dueItem, $originalAmount, $event->event_date);

        return $dueItem;
    }

    /**
     * @return int Total discount in cents, clamped so it never exceeds the original amount.
     */
    private function applyDiscounts(int $studentId, int $feeCategoryId, int $originalAmount, string $dueDate): int
    {
        $remaining = $originalAmount;

        foreach ($this->applicableDiscounts($studentId, $feeCategoryId, $dueDate) as $discount) {
            $applied = min($this->discountAmountInCents($discount, $originalAmount), $remaining);

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
