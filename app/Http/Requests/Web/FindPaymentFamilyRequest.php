<?php

namespace App\Http\Requests\Web;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

class FindPaymentFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Payment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'family_code' => ['nullable', 'string', 'max:255'],
        ];
    }
}
