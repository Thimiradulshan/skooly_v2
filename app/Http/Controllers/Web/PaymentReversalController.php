<?php

namespace App\Http\Controllers\Web;

use App\Actions\Payments\ApprovePaymentReversal;
use App\Actions\Payments\RequestPaymentReversal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ApprovePaymentReversalRequest;
use App\Http\Requests\Web\StorePaymentReversalRequest;
use App\Models\Payment;
use App\Models\PaymentReversal;
use App\Models\PaymentReversalAllocation;
use InvalidArgumentException;
use RuntimeException;

class PaymentReversalController extends Controller
{
    public function index()
    {
        $paymentReversals = PaymentReversal::query()
            ->with(['originalPayment.family', 'requestedBy', 'approvedBy', 'correctionReceipt'])
            ->latest('id')
            ->paginate(20);

        return view('payment-reversals.index', compact('paymentReversals'));
    }

    public function create(Payment $payment)
    {
        $payment->load(['family', 'allocations.studentDueItem']);
        $reversedAmounts = PaymentReversalAllocation::query()
            ->whereIn('payment_allocation_id', $payment->allocations->modelKeys())
            ->whereHas('paymentReversal', fn ($query) => $query->where('status', PaymentReversal::STATUS_APPROVED))
            ->selectRaw('payment_allocation_id, sum(selected_amount) as selected_amount')
            ->groupBy('payment_allocation_id')
            ->pluck('selected_amount', 'payment_allocation_id');

        $payment->allocations->each(function ($allocation) use ($reversedAmounts): void {
            $allocation->setAttribute('reversible_amount', $this->fromCents(
                $this->toCents($allocation->amount) - $this->toCents($reversedAmounts[$allocation->id] ?? '0.00'),
            ));
        });

        return view('payment-reversals.create', compact('payment'));
    }

    public function store(StorePaymentReversalRequest $request, Payment $payment, RequestPaymentReversal $requestPaymentReversal)
    {
        try {
            /** @var array<int, string|null> $requestedAllocations */
            $requestedAllocations = $request->validated('allocations');
            $allocations = [];

            foreach ($requestedAllocations as $paymentAllocationId => $amount) {
                if ($amount !== null && $amount !== '') {
                    $allocations[] = [
                        'payment_allocation_id' => $paymentAllocationId,
                        'amount' => $amount,
                    ];
                }
            }

            /** @var array<int, array{payment_allocation_id: int, amount: string}> $allocations */
            $paymentReversal = $requestPaymentReversal->handle($payment, $request->string('reason')->toString(), $allocations, $request->user());
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->route('payment-reversals.show', $paymentReversal)->with('status', 'Payment reversal requested.');
    }

    public function show(PaymentReversal $paymentReversal)
    {
        $paymentReversal->load(['originalPayment.family', 'originalPayment.receipt', 'requestedBy', 'approvedBy', 'allocations.paymentAllocation.studentDueItem', 'correctionReceipt']);

        return view('payment-reversals.show', compact('paymentReversal'));
    }

    public function approve(ApprovePaymentReversalRequest $request, PaymentReversal $paymentReversal, ApprovePaymentReversal $approvePaymentReversal)
    {
        try {
            $approvePaymentReversal->handle($paymentReversal, $request->string('receipt_no')->toString(), $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['payment_reversal' => $exception->getMessage()]);
        }

        return redirect()->route('payment-reversals.show', $paymentReversal)->with('status', 'Payment reversal approved and due items reopened.');
    }

    private function toCents(string|int|float $amount): int
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

    private function fromCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
