<?php

namespace App\Http\Requests\Web;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GeneratePaymentRemindersRequest extends FormRequest
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
            'as_of_date' => ['required', 'date'],
            'upcoming_window_days' => ['nullable', 'integer', 'min:0'],
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'id')->where('is_archived', 0)],
            'family_id' => ['nullable', 'integer', 'exists:families,id'],
        ];
    }
}
