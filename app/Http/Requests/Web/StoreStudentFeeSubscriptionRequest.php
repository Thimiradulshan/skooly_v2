<?php

namespace App\Http\Requests\Web;

use App\Academic\ActiveAcademicYear;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentFeeSubscriptionRequest extends FormRequest
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
            'fee_category_id' => [
                'required',
                'integer',
                Rule::exists('fee_categories', 'id')->where('is_opt_in', true),
            ],
            'academic_year_id' => ['required', 'integer', (new ActiveAcademicYear)->validationRule()],
            'is_active' => ['nullable', 'boolean'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fee_category_id.exists' => 'Only opt-in fee categories can be subscribed.',
        ];
    }
}
