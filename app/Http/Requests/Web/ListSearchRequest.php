<?php

namespace App\Http\Requests\Web;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class ListSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Role::ADMIN]) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}
