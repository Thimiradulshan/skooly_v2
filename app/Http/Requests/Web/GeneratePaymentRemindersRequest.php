<?php

namespace App\Http\Requests\Web;

use App\Academic\ActiveAcademicYear;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class GeneratePaymentRemindersRequest extends FormRequest
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
            'as_of_date' => ['required', 'date'],
            'upcoming_window_days' => ['nullable', 'integer', 'min:0'],
            'academic_year_id' => ['nullable', 'integer', (new ActiveAcademicYear)->validationRule()],
            'family_id' => ['nullable', 'integer', 'exists:families,id'],
        ];
    }
}
