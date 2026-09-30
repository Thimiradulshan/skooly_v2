<?php

namespace App\Http\Requests\Web;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeeCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', Rule::unique('fee_categories', 'name')],
            'is_recurring' => ['nullable', 'boolean'],
            'is_opt_in' => ['nullable', 'boolean'],
        ];
    }
}
