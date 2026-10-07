<?php

namespace App\Http\Requests\Web;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventChargeRequest extends FormRequest
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
            'grade_id' => [
                'required',
                'integer',
                Rule::exists('grades', 'id')->where('is_archived', 0),
                Rule::unique('event_charges', 'grade_id')->where('event_id', $this->route('event')->id),
            ],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
        ];
    }
}
