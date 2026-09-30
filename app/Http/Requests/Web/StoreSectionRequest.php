<?php

namespace App\Http\Requests\Web;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSectionRequest extends FormRequest
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
        return [
            'grade_id' => ['required', 'integer', Rule::exists('grades', 'id')],
            'name' => ['required', 'string', 'max:255', Rule::unique('sections', 'name')->where('grade_id', $this->integer('grade_id'))],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ];
    }
}
