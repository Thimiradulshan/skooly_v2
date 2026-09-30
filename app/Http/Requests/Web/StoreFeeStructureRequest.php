<?php

namespace App\Http\Requests\Web;

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
            'grade_id' => ['required', 'integer', 'exists:grades,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['required', 'string', 'max:255'],
        ];
    }
}
