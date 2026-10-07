<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ListPaymentsRequest;
use App\Http\Requests\Web\StoreManualPaymentRequest;
use App\Models\Family;
use App\Models\Payment;
use App\Models\StudentDueItem;
use Carbon\Carbon;

class PaymentCollectionController extends Controller
{
    public function index(ListPaymentsRequest $request)
    {
        $sort = $request->input('sort', 'paid_at');
        $direction = $request->input('direction', 'desc');
        $search = $request->string('search')->trim()->toString();

        $payments = Payment::query()
            ->with(['family', 'receipt'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('payment_reference', 'like', "%{$search}%")
                        ->orWhereHas('family', fn ($family) => $family->where('family_code', 'like', "%{$search}%"))
                        ->orWhereHas('receipt', fn ($receipt) => $receipt->where('receipt_no', 'like', "%{$search}%"));
                });
            })
            ->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('payments.index', compact('payments', 'search', 'sort', 'direction'));
    }

    public function create(Family $family)
    {
        return view('payments.create', [
            'family' => $family,
            'dueItems' => StudentDueItem::query()
                ->with('student')
                ->whereHas('student', fn ($student) => $student->where('family_id', $family->id))
                ->where('balance_amount', '>', 0)
                ->orderBy('due_date')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(StoreManualPaymentRequest $request, Family $family)
    {
        $allocations = array_values(array_filter(
            $request->input('allocations'),
            fn (array $allocation): bool => filled($allocation['amount'] ?? null),
        ));

        $payment = Payment::recordManual(
            $family,
            $request->string('receipt_no')->toString(),
            $request->string('method')->toString(),
            (string) $request->input('amount'),
            array_map(fn (array $allocation): array => [
                'student_due_item_id' => (int) $allocation['student_due_item_id'],
                'amount' => (string) $allocation['amount'],
            ], $allocations),
            null,
            $request->input('notes'),
            Carbon::parse($request->string('paid_at')->toString()),
            $request->user(),
        );

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', 'Payment recorded.');
    }

    public function show(Payment $payment)
    {
        $payment->load(['family', 'allocations.studentDueItem.student', 'receipt', 'reversal']);

        return view('payments.show', ['payment' => $payment]);
    }
}
