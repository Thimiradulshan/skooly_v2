<?php

namespace App\Http\Requests\Web;

use App\Models\EventParticipation;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventParticipationRequest extends FormRequest
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
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer', 'exists:students,id'],
            'status' => ['required', 'string', Rule::in([
                EventParticipation::STATUS_OPTED_IN,
                EventParticipation::STATUS_OPTED_OUT,
            ])],
        ];
    }
}
