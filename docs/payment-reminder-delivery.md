# Payment Reminder Delivery (Local Operations)

Apply additive migrations with `php artisan migrate --no-interaction`. In an Admin Guardian record, record consent with an authorization reference, then request the verification email and follow its link. Consent never verifies an email.

Generate internal reminders from the Admin payment-reminders screen. Inspect proposed work safely with `php artisan reminders:schedule-delivery --dry-run`; schedule eligible attempts with `php artisan reminders:schedule-delivery`; process a bounded local worker with `php artisan queue:work --max-jobs=10 --max-time=60`.

Local delivery uses Laravel's log or array mailer only. Inspect the configured Laravel log channel and the reminder detail page for attempt history. Transport acceptance does not prove inbox delivery. Production SMTP is disabled by the application delivery guard; provider configuration, webhooks, bounce handling, and confirmed delivery remain future work.
