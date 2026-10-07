<?php

namespace App\Http\Requests\Web;

use App\Models\Role;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::ADMIN) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'dob' => ['required', 'date'],
            'gender' => ['required', 'string', 'max:255'],
            'admission_no' => ['required', 'string', 'max:255', Rule::unique('students', 'admission_no')->ignore($this->route('student'))],
            'photo_path' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in([Student::STATUS_PENDING_REGISTRATION, Student::STATUS_ACTIVE, Student::STATUS_INACTIVE, Student::STATUS_WITHDRAWN, Student::STATUS_GRADUATED])],
        ];
    }
}
