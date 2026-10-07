<?php

namespace App\Http\Requests\Web;

use App\Models\PaymentReversal;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprovePaymentReversalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $paymentReversal = $this->route('paymentReversal');
        $user = $this->user();

        return $paymentReversal instanceof PaymentReversal
            && $user !== null
            && $user->hasRole(Role::ADMIN)
            && $user->can('approve', $paymentReversal);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'receipt_no' => ['required', 'string', 'max:255', Rule::unique('correction_receipts', 'receipt_no')],
        ];
    }
}
