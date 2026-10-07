<?php

namespace App\Http\Requests\Web;

use App\Models\AuditLog;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FilterAuditLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::ADMIN) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['nullable', 'string', Rule::in($this->actions())],
            'actor_user_id' => ['nullable', 'integer', Rule::in($this->actorIds())],
            'auditable_type' => ['nullable', 'string', Rule::in($this->auditableTypes())],
            'occurred_at_from' => ['nullable', 'date_format:Y-m-d'],
            'occurred_at_to' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (
                    $validator->errors()->hasAny(['occurred_at_from', 'occurred_at_to'])
                    || $this->input('occurred_at_from') === null
                    || $this->input('occurred_at_to') === null
                ) {
                    return;
                }

                if ($this->input('occurred_at_from') > $this->input('occurred_at_to')) {
                    $validator->errors()->add('occurred_at_to', 'The occurred at to date must be after or equal to the occurred at from date.');
                }
            },
        ];
    }

    /**
     * @return array<int, string>
     */
    private function actions(): array
    {
        return AuditLog::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function actorIds(): array
    {
        return AuditLog::query()
            ->whereNotNull('actor_user_id')
            ->distinct()
            ->orderBy('actor_user_id')
            ->pluck('actor_user_id')
            ->map(fn (int $actorUserId): int => $actorUserId)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function auditableTypes(): array
    {
        return AuditLog::query()
            ->whereNotNull('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type')
            ->all();
    }
}
