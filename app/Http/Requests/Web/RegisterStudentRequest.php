<?php

namespace App\Http\Requests\Web;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RegisterStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $statuses = [
            Student::STATUS_PENDING_REGISTRATION,
            Student::STATUS_ACTIVE,
            Student::STATUS_INACTIVE,
            Student::STATUS_WITHDRAWN,
            Student::STATUS_GRADUATED,
        ];

        return [
            'name' => ['required', 'string', 'max:255'],
            'dob' => ['required', 'date'],
            'gender' => ['required', 'string', 'max:255'],
            'admission_no' => ['required', 'string', 'max:255', Rule::unique('students', 'admission_no')],
            'photo_path' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in($statuses)],
            'guardian_ids' => ['nullable', 'array'],
            'guardian_ids.*' => ['integer', 'exists:guardians,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'academic_year_id.required_with' => 'Academic year, grade, and section must all be provided to create an enrollment.',
            'grade_id.required_with' => 'Academic year, grade, and section must all be provided to create an enrollment.',
            'section_id.required_with' => 'Academic year, grade, and section must all be provided to create an enrollment.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $provided = array_filter([
                    $this->input('academic_year_id'),
                    $this->input('grade_id'),
                    $this->input('section_id'),
                ]);

                if ($provided !== [] && count($provided) !== 3) {
                    $validator->errors()->add('section_id', 'Academic year, grade, and section must all be provided to create an enrollment.');
                }
            },
        ];
    }
}
