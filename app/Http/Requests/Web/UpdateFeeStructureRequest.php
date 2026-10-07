<?php

namespace App\Http\Requests\Web;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeeStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Role::ADMIN]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $feeStructure = $this->route('feeStructure');

        return [
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'frequency' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fee_structures', 'frequency')
                    ->where('fee_category_id', $feeStructure->fee_category_id)
                    ->where('grade_id', $feeStructure->grade_id)
                    ->where('academic_year_id', $feeStructure->academic_year_id)
                    ->ignore($feeStructure),
            ],
        ];
    }
}
