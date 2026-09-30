<?php

namespace App\Http\Requests\Web;

use App\Models\Grade;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGradeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Role::ADMIN]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Grade $grade */
        $grade = $this->route('grade');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('grades', 'name')->ignore($grade)],
            'sequence_order' => ['required', 'integer', 'min:1', 'max:65535', Rule::unique('grades', 'sequence_order')->ignore($grade)],
        ];
    }
}
