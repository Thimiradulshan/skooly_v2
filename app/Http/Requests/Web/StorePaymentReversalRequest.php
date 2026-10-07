<?php

namespace App\Http\Requests\Web;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePaymentReversalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::ACCOUNTANT) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*' => ['nullable', 'decimal:0,2', 'gt:0'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var array<int, string|null> $allocations */
            $allocations = $this->input('allocations', []);

            if (collect($allocations)->filter(fn (?string $amount): bool => $amount !== null && $amount !== '')->isEmpty()) {
                $validator->errors()->add('allocations', 'Select at least one original allocation to reverse.');
            }
        }];
    }
}
