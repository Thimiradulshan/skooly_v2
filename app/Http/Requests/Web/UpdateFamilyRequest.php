<?php

namespace App\Http\Requests\Web;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFamilyRequest extends FormRequest
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
            'family_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('families', 'family_code')->ignore($this->route('family')),
            ],
            'address' => ['nullable', 'string'],
            'home_contact_no' => ['nullable', 'string', 'max:255'],
            'combined_billing_enabled' => ['nullable', 'boolean'],
        ];
    }
}
