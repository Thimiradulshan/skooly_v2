<?php

namespace App\Http\Requests\Web;

use App\Academic\ActiveAcademicYear;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeeStructureRequest extends FormRequest
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
                'exists:fee_categories,id',
                Rule::unique('fee_structures', 'id')->where(fn ($query) => $query
                    ->where('grade_id', $this->input('grade_id'))
                    ->where('academic_year_id', $this->input('academic_year_id'))
                    ->where('frequency', $this->input('frequency'))),
            ],
            'grade_id' => ['required', 'integer', Rule::exists('grades', 'id')->where('is_archived', 0)],
            'academic_year_id' => ['required', 'integer', (new ActiveAcademicYear)->validationRule()],
            'amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['required', 'string', 'max:255'],
        ];
    }
}
