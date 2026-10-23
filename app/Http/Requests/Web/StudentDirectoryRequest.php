<?php

namespace App\Http\Requests\Web;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StudentDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Student::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'status' => [
                'nullable',
                Rule::in([
                    Student::STATUS_PENDING_REGISTRATION,
                    Student::STATUS_ACTIVE,
                    Student::STATUS_INACTIVE,
                    Student::STATUS_WITHDRAWN,
                    Student::STATUS_GRADUATED,
                ]),
            ],
        ];
    }

    /**
     * Grade and section are year-specific through Enrollment, so they need an academic year.
     *
     * @return array<int, callable(Validator): void>
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
