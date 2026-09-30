<?php

namespace App\Actions\Notifications;

use App\Models\Family;
use App\Models\Guardian;
use App\Models\PaymentReminder;
use App\Models\StudentDueItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GeneratePaymentReminders
{
    /**
     * Build internal reminder/outbox records for upcoming and overdue due items.
     *
     * This does not send anything. It only records what should be sent.
     *
     * @return int Number of reminder records created.
     */
    public function handle(
        string $asOfDate,
        int $upcomingWindowDays = 7,
        ?int $academicYearId = null,
        ?int $familyId = null,
    ): int {
        return DB::transaction(function () use ($asOfDate, $upcomingWindowDays, $academicYearId, $familyId): int {
            $upcomingLimit = date('Y-m-d', strtotime($asOfDate.' +'.$upcomingWindowDays.' days'));

            $dueItems = StudentDueItem::query()
                ->with(['student.family', 'student.guardians'])
                ->where('balance_amount', '>', 0)
                ->whereIn('status', [StudentDueItem::STATUS_UNPAID, StudentDueItem::STATUS_PARTIALLY_PAID])
                ->whereNotNull('due_date')
                ->where(function ($query) use ($asOfDate, $upcomingLimit): void {
                    $query->whereDate('due_date', '<', $asOfDate)
                        ->orWhere(function ($upcoming) use ($asOfDate, $upcomingLimit): void {
                            $upcoming->whereDate('due_date', '>=', $asOfDate)
                                ->whereDate('due_date', '<=', $upcomingLimit);
                        });
                })
                ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
                ->when($familyId, fn ($query) => $query->whereHas(
                    'student',
                    fn ($student) => $student->where('students.family_id', $familyId)
                ))
                ->orderBy('id')
                ->get();

            return $this->createReminders($dueItems, $asOfDate);
        });
    }

    /**
     * @param  Collection<int, StudentDueItem>  $dueItems
     */
    private function createReminders(Collection $dueItems, string $asOfDate): int
    {
        $pending = [];

        foreach ($dueItems as $dueItem) {
            $family = $dueItem->student->family;
            $reminderType = $this->reminderType($dueItem, $asOfDate);

            foreach ($dueItem->student->guardians as $guardian) {
                $key = $family->combined_billing_enabled
                    ? "family:{$family->id}:guardian:{$guardian->id}:type:{$reminderType}:date:{$asOfDate}"
                    : "due:{$dueItem->id}:guardian:{$guardian->id}:type:{$reminderType}:date:{$asOfDate}";

                $pending[$key] ??= [
                    'family' => $family,
                    'guardian' => $guardian,
                    'reminder_type' => $reminderType,
                    'due_items' => [],
                    'student_due_item_id' => $family->combined_billing_enabled ? null : $dueItem->id,
                ];

                $pending[$key]['due_items'][] = $dueItem;
            }
        }

        $created = 0;

        foreach ($pending as $key => $reminder) {
            if (PaymentReminder::query()->where('reminder_key', $key)->exists()) {
                continue;
            }

            PaymentReminder::query()->create([
                'family_id' => $reminder['family']->id,
                'guardian_id' => $reminder['guardian']->id,
                'student_due_item_id' => $reminder['student_due_item_id'],
                'reminder_type' => $reminder['reminder_type'],
                'status' => PaymentReminder::STATUS_PENDING,
                'due_item_ids' => array_map(
                    fn (StudentDueItem $dueItem) => $dueItem->id,
                    $reminder['due_items']
                ),
                'message_snapshot' => $this->messageSnapshot(
                    $reminder['family'],
                    $reminder['guardian'],
                    $reminder['reminder_type'],
                    $asOfDate,
                    $reminder['due_items']
                ),
                'reminder_key' => $key,
            ]);

            $created++;
        }

        return $created;
    }

    private function reminderType(StudentDueItem $dueItem, string $asOfDate): string
    {
        return $this->dueDate($dueItem)->toDateString() < $asOfDate
            ? PaymentReminder::TYPE_OVERDUE
            : PaymentReminder::TYPE_UPCOMING;
    }

    /**
     * @param  array<int, StudentDueItem>  $dueItems
     * @return array<string, mixed>
     */
    private function messageSnapshot(
        Family $family,
        Guardian $guardian,
        string $reminderType,
        string $asOfDate,
        array $dueItems,
    ): array {
        $totalCents = 0;

        $items = array_map(function (StudentDueItem $dueItem) use (&$totalCents): array {
            $totalCents += self::toCents($dueItem->balance_amount);

            return [
                'student_due_item_id' => $dueItem->id,
                'student_id' => $dueItem->student_id,
                'student_name' => $dueItem->student->name,
                'admission_no' => $dueItem->student->admission_no,
                'description' => $dueItem->description,
                'due_date' => $this->dueDate($dueItem)->toDateString(),
                'balance_amount' => self::fromCents(self::toCents($dueItem->balance_amount)),
                'status' => $dueItem->status,
            ];
        }, $dueItems);

        return [
            'family_id' => $family->id,
            'family_code' => $family->family_code,
            'guardian_id' => $guardian->id,
            'guardian_name' => $guardian->name,
            'reminder_type' => $reminderType,
            'as_of_date' => $asOfDate,
            'total_balance_amount' => self::fromCents($totalCents),
            'due_item_count' => count($items),
            'due_items' => $items,
        ];
    }

    private function dueDate(StudentDueItem $dueItem): Carbon
    {
        return Carbon::parse($dueItem->due_date);
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
