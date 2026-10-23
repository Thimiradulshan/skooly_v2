<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaymentReminderEmail extends Mailable
{
    public function __construct(
        public string $schoolName,
        public string $studentName,
        public string $outstandingAmount,
        public string $dueDate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Skooly payment reminder');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.payment-reminder');
    }
}
