<?php

namespace App\Http\Requests\Web;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentDiscountRequest extends FormRequest
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
            'fee_category_id' => ['required', 'integer', 'exists:fee_categories,id'],
            'type' => ['required', 'string', 'max:255'],
            'value' => ['required', 'numeric', 'min:0'],
            'value_type' => ['nullable', 'in:amount,percentage'],
            'is_active' => ['nullable', 'boolean'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }
}
