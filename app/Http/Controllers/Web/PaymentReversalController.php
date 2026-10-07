<?php

namespace App\Http\Controllers\Web;

use App\Actions\Payments\ApprovePaymentReversal;
use App\Actions\Payments\RequestPaymentReversal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ApprovePaymentReversalRequest;
use App\Http\Requests\Web\StorePaymentReversalRequest;
use App\Models\Payment;
use App\Models\PaymentReversal;
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
        $payment->load('family');

        return view('payment-reversals.create', compact('payment'));
    }

    public function store(StorePaymentReversalRequest $request, Payment $payment, RequestPaymentReversal $requestPaymentReversal)
    {
        try {
            $paymentReversal = $requestPaymentReversal->handle($payment, $request->string('reason')->toString(), $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->route('payment-reversals.show', $paymentReversal)->with('status', 'Payment reversal requested.');
    }

    public function show(PaymentReversal $paymentReversal)
    {
        $paymentReversal->load(['originalPayment.family', 'originalPayment.receipt', 'requestedBy', 'approvedBy', 'correctionReceipt']);

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
}
