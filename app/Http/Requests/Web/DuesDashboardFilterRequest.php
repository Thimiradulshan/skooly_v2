<?php

namespace App\Http\Requests\Web;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DuesDashboardFilterRequest extends FormRequest
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
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'fee_category_id' => ['nullable', 'integer', 'exists:fee_categories,id'],
            'family_id' => ['nullable', 'integer', 'exists:families,id'],
            'due_date_from' => ['nullable', 'date'],
            'due_date_to' => ['nullable', 'date', 'after_or_equal:due_date_from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'grade_id.academic_year_required' => 'An academic year is required when filtering by grade.',
            'section_id.academic_year_required' => 'An academic year is required when filtering by section.',
        ];
    }

    /**
     * Grade and section are year-specific through Enrollment, so they need an academic year.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (filled($this->input('academic_year_id'))) {
                    return;
                }

                if (filled($this->input('grade_id'))) {
                    $validator->errors()->add('grade_id', 'An academic year is required when filtering by grade.');
                }

                if (filled($this->input('section_id'))) {
                    $validator->errors()->add('section_id', 'An academic year is required when filtering by section.');
                }
            },
        ];
    }
}
