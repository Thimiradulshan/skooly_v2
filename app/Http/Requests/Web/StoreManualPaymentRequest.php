<?php

namespace App\Http\Requests\Web;

use App\Models\Family;
use App\Models\Role;
use App\Models\StudentDueItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreManualPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasAnyRole([Role::ADMIN]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'receipt_no' => ['required', 'string', 'max:255', Rule::unique('receipts', 'receipt_no')],
            'method' => ['required', 'string', 'max:255'],
            'paid_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.student_due_item_id' => ['required', 'integer', 'exists:student_due_items,id'],
            'allocations.*.amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0.01'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $family = $this->route('family');

                if (! $family instanceof Family || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $allocationTotal = 0;
                $allocationCount = 0;

                foreach ($this->input('allocations', []) as $index => $allocation) {
                    if (blank($allocation['amount'] ?? null)) {
                        continue;
                    }

                    $amount = $this->toCents($allocation['amount']);
                    $allocationTotal += $amount;
                    $allocationCount++;
                    $dueItem = StudentDueItem::query()->find($allocation['student_due_item_id']);

                    if ($dueItem === null) {
                        continue;
                    }

                    if ($dueItem->student->family_id !== $family->id) {
                        $validator->errors()->add("allocations.{$index}.student_due_item_id", 'Due item must belong to the selected family.');
                    }

                    if ($amount > $this->toCents($dueItem->balance_amount)) {
                        $validator->errors()->add("allocations.{$index}.amount", 'Allocation cannot exceed the due item balance.');
                    }
                }

                if ($allocationCount === 0) {
                    $validator->errors()->add('allocations', 'At least one due item allocation is required.');
                } elseif ($allocationTotal !== $this->toCents($this->input('amount'))) {
                    $validator->errors()->add('allocations', 'Allocation total must equal payment amount.');
                }
            },
        ];
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
}
