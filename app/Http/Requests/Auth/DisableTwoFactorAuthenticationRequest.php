<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class DisableTwoFactorAuthenticationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string'],
            'code' => ['required', 'string', 'regex:/^(?:[0-9]{6}|[A-Fa-f0-9]{10})$/'],
        ];
    }
}
