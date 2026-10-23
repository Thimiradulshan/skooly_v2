<?php

namespace App\Console\Commands;

use App\Actions\Notifications\PaymentReminderEmailDeliveryEligibility;
use App\Actions\Notifications\QueuePaymentReminderDeliveryAttempt;
use App\Jobs\DeliverPaymentReminderEmail;
use App\Models\PaymentReminder;
use App\Models\PaymentReminderDeliveryAttempt;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

#[Signature('reminders:schedule-delivery {--dry-run} {--limit=100}')]
#[Description('Queue eligible payment reminder email delivery attempts.')]
class SchedulePaymentReminderDeliveries extends Command
{
    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $counters = ['scanned' => 0, 'eligible' => 0, 'scheduled' => 0, 'skipped' => 0];

        PaymentReminder::query()->where('status', PaymentReminder::STATUS_PENDING)->orderBy('id')->limit($limit)->get()
            ->each(function (PaymentReminder $reminder) use (&$counters, $dryRun): void {
                $counters['scanned']++;

                if (! (new PaymentReminderEmailDeliveryEligibility)->handle($reminder) || count($reminder->due_item_ids) !== 1 || $this->hasQueuedOrRecentAttempt($reminder)) {
                    $counters['skipped']++;

                    return;
                }

                $counters['eligible']++;

                if ($dryRun) {
                    return;
                }

                try {
                    $attempt = (new QueuePaymentReminderDeliveryAttempt)->handle($reminder);
                } catch (\RuntimeException) {
                    $counters['skipped']++;

                    return;
                }

                Bus::dispatch(new DeliverPaymentReminderEmail($attempt->id));
                $counters['scheduled']++;
            });

        $this->info("Scanned: {$counters['scanned']}; eligible: {$counters['eligible']}; scheduled: {$counters['scheduled']}; skipped: {$counters['skipped']}.");

        return self::SUCCESS;
    }

    private function hasQueuedOrRecentAttempt(PaymentReminder $reminder): bool
    {
        return $reminder->deliveryAttempts()->where(function ($query): void {
            $query->where('status', PaymentReminderDeliveryAttempt::STATUS_QUEUED)
                ->orWhere('created_at', '>=', now()->subDays(7));
        })->exists();
    }
}
