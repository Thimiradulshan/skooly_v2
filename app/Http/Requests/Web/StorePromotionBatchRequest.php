<?php

namespace App\Http\Requests\Web;

use App\Academic\ActiveAcademicYear;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromotionBatchRequest extends FormRequest
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
            'source_academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'target_academic_year_id' => [
                'required',
                'integer',
                (new ActiveAcademicYear)->validationRule(),
                Rule::notIn([$this->input('source_academic_year_id')]),
            ],
            'source_section_ids' => ['required', 'array', 'min:1'],
            'source_section_ids.*' => ['required', 'integer', Rule::exists('sections', 'id')->where('is_archived', 0)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_academic_year_id.not_in' => 'Target academic year must differ from source academic year.',
        ];
    }
}
