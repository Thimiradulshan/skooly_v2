<?php

namespace App\Http\Requests\Web;

use App\Models\PaymentReminder;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentReminderFilterRequest extends FormRequest
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
            'family_id' => ['nullable', 'integer', 'exists:families,id'],
            'guardian_id' => ['nullable', 'integer', 'exists:guardians,id'],
            'reminder_type' => ['nullable', Rule::in([
                PaymentReminder::TYPE_UPCOMING,
                PaymentReminder::TYPE_OVERDUE,
            ])],
            'status' => ['nullable', Rule::in([
                PaymentReminder::STATUS_PENDING,
                PaymentReminder::STATUS_SENT,
                PaymentReminder::STATUS_CANCELLED,
            ])],
        ];
    }
}
