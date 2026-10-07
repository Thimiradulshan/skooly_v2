<?php

namespace App\Http\Requests\Web;

use App\Academic\ActiveAcademicYear;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
            'fee_category_id' => ['required', 'integer', 'exists:fee_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'is_mandatory' => ['nullable', 'boolean'],
        ];
    }
}
