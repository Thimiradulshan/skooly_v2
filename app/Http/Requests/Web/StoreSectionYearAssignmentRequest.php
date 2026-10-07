<?php

namespace App\Http\Requests\Web;

use App\Academic\ActiveAcademicYear;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSectionYearAssignmentRequest extends FormRequest
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
            'academic_year_id' => ['required', 'integer', (new ActiveAcademicYear)->validationRule()],
            'section_id' => ['required', 'integer', Rule::exists('sections', 'id')->where('is_archived', 0)],
            'class_in_charge_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
