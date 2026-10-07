<?php

namespace App\Http\Requests\Web;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:255', Rule::unique('subjects', 'code')->ignore($this->route('subject'))],
            'name' => ['required', 'string', 'max:255', Rule::unique('subjects', 'name')->ignore($this->route('subject'))],
        ];
    }
}
