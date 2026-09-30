<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'family_code' => ['required', 'string', 'max:255', Rule::unique('families', 'family_code')],
            'address' => ['nullable', 'string'],
            'home_contact_no' => ['nullable', 'string', 'max:255'],
            'combined_billing_enabled' => ['nullable', 'boolean'],
            'guardians' => ['nullable', 'array'],
            'guardians.*.name' => ['required_with:guardians', 'string', 'max:255'],
            'guardians.*.relationship' => ['nullable', 'string', 'max:255'],
            'guardians.*.contact_no' => ['nullable', 'string', 'max:255'],
            'guardians.*.email' => ['nullable', 'email', 'max:255'],
            'guardians.*.nic' => ['nullable', 'string', 'max:255'],
        ];
    }
}
