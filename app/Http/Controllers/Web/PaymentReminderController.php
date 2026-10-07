<?php

namespace App\Http\Controllers\Web;

use App\Actions\Notifications\CancelPaymentReminder;
use App\Actions\Notifications\GeneratePaymentReminders;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CancelPaymentReminderRequest;
use App\Http\Requests\Web\GeneratePaymentRemindersRequest;
use App\Http\Requests\Web\PaymentReminderFilterRequest;
use App\Models\AcademicYear;
use App\Models\Family;
use App\Models\Guardian;
use App\Models\PaymentReminder;
use RuntimeException;

class PaymentReminderController extends Controller
{
    public function index(PaymentReminderFilterRequest $request)
    {
        $reminders = PaymentReminder::query()
            ->with(['family', 'guardian', 'studentDueItem.student'])
            ->when($request->integer('family_id'), fn ($query, $familyId) => $query->where('family_id', $familyId))
            ->when($request->integer('guardian_id'), fn ($query, $guardianId) => $query->where('guardian_id', $guardianId))
            ->when($request->input('reminder_type'), fn ($query, $type) => $query->where('reminder_type', $type))
            ->when($request->input('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('payment-reminders.index', [
            'reminders' => $reminders,
            'families' => Family::query()->orderBy('family_code')->get(),
            'guardians' => Guardian::query()->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('payment-reminders.create', [
            'academicYears' => AcademicYear::query()->active()->orderBy('id')->get(),
            'families' => Family::query()->orderBy('family_code')->get(),
        ]);
    }

    public function store(GeneratePaymentRemindersRequest $request, GeneratePaymentReminders $generatePaymentReminders)
    {
        $created = $generatePaymentReminders->handle(
            $request->string('as_of_date')->toString(),
            $request->integer('upcoming_window_days', 7),
            $request->integer('academic_year_id') ?: null,
            $request->integer('family_id') ?: null,
            $request->user(),
        );

        return redirect()
            ->route('payment-reminders.index')
            ->with('status', "Reminder records generated. {$created} reminder(s) created.");
    }

    public function show(PaymentReminder $paymentReminder)
    {
        $paymentReminder->load(['family', 'guardian', 'studentDueItem.student']);

        return view('payment-reminders.show', ['paymentReminder' => $paymentReminder]);
    }

    public function cancel(
        CancelPaymentReminderRequest $request,
        PaymentReminder $paymentReminder,
        CancelPaymentReminder $cancelPaymentReminder,
    ) {
        try {
            $cancelPaymentReminder->handle($paymentReminder);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['payment_reminder' => $exception->getMessage()]);
        }

        return redirect()
            ->route('payment-reminders.show', $paymentReminder)
            ->with('status', 'Payment reminder cancelled. No message was sent.');
    }
}
