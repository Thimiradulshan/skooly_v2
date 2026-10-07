<?php

namespace App\Http\Requests\Web;

use App\Academic\ActiveAcademicYear;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class GenerateRecurringDuesRequest extends FormRequest
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
            'academic_year_id' => ['required', 'integer', (new ActiveAcademicYear)->validationRule()],
            'due_date' => ['required', 'date'],
            'cycle_key' => ['nullable', 'string', 'max:255'],
        ];
    }
}
