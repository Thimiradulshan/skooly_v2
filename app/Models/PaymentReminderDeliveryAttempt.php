<?php

namespace App\Models;

use Database\Factories\PaymentReminderDeliveryAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payment_reminder_id', 'attempt_number', 'guardian_id', 'recipient_email', 'status', 'transport_accepted_at', 'failed_at', 'failure_classification', 'failure_message'])]
class PaymentReminderDeliveryAttempt extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_TRANSPORT_ACCEPTED = 'transport_accepted';

    public const STATUS_FAILED = 'failed';

    public const FAILURE_TRANSPORT_UNAVAILABLE = 'transport_unavailable';

    public const FAILURE_RECIPIENT_REJECTED = 'recipient_rejected';

    public const FAILURE_UNEXPECTED = 'unexpected';

    /** @use HasFactory<PaymentReminderDeliveryAttemptFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $hidden = [
        'recipient_email',
        'failure_message',
    ];

    /**
     * @return BelongsTo<PaymentReminder, $this>
     */
    public function paymentReminder(): BelongsTo
    {
        return $this->belongsTo(PaymentReminder::class);
    }

    /**
     * @return BelongsTo<Guardian, $this>
     */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recipient_email' => 'encrypted',
            'transport_accepted_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public static function failureMessage(string $classification): string
    {
        return match ($classification) {
            self::FAILURE_TRANSPORT_UNAVAILABLE => 'Email transport is unavailable.',
            self::FAILURE_RECIPIENT_REJECTED => 'Recipient address was rejected.',
            self::FAILURE_UNEXPECTED => 'Email delivery failed unexpectedly.',
            default => throw new \InvalidArgumentException('Unsupported delivery failure classification.'),
        };
    }
}
