<?php

namespace App\Jobs;

use App\Actions\Notifications\MarkPaymentReminderDeliveryAttemptFailed;
use App\Actions\Notifications\MarkPaymentReminderDeliveryAttemptTransportAccepted;
use App\Actions\Notifications\PaymentReminderEmailDeliveryEligibility;
use App\Mail\PaymentReminderEmail;
use App\Models\PaymentReminderDeliveryAttempt;
use App\Models\StudentDueItem;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class DeliverPaymentReminderEmail implements ShouldBeUnique
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public int $attemptId) {}

    public function uniqueId(): string
    {
        return (string) $this->attemptId;
    }

    public function handle(): void
    {
        Cache::lock('payment-reminder-delivery-attempt:'.$this->attemptId, 120)->block(0, function (): void {
            $attempt = PaymentReminderDeliveryAttempt::query()->find($this->attemptId);

            if ($attempt === null || $attempt->status !== PaymentReminderDeliveryAttempt::STATUS_QUEUED) {
                return;
            }

            $reminder = $attempt->paymentReminder;

            if (! (new PaymentReminderEmailDeliveryEligibility)->handle($reminder) || $attempt->recipient_email !== $reminder->guardian->email) {
                (new MarkPaymentReminderDeliveryAttemptFailed)->handle($attempt, PaymentReminderDeliveryAttempt::FAILURE_RECIPIENT_REJECTED);

                return;
            }

            $dueItems = StudentDueItem::query()->with('student')->whereKey($reminder->due_item_ids)->get();

            if ($dueItems->count() !== 1) {
                (new MarkPaymentReminderDeliveryAttemptFailed)->handle($attempt, PaymentReminderDeliveryAttempt::FAILURE_RECIPIENT_REJECTED);

                return;
            }

            $dueItem = $dueItems->sole();

            if (! in_array(config('mail.default'), ['log', 'array'], true)) {
                throw new RuntimeException('External email transports are not enabled for payment reminder delivery.');
            }

            Mail::to($attempt->recipient_email)->send(new PaymentReminderEmail(config('app.name'), $dueItem->student->name, $dueItem->balance_amount, $dueItem->due_date->toDateString()));
            (new MarkPaymentReminderDeliveryAttemptTransportAccepted)->handle($attempt);
        });
    }

    public function failed(\Throwable $exception): void
    {
        $attempt = PaymentReminderDeliveryAttempt::query()->find($this->attemptId);

        if ($attempt?->status === PaymentReminderDeliveryAttempt::STATUS_QUEUED) {
            (new MarkPaymentReminderDeliveryAttemptFailed)->handle($attempt, PaymentReminderDeliveryAttempt::FAILURE_TRANSPORT_UNAVAILABLE);
        }
    }
}
