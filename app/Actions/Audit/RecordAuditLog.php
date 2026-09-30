<?php

namespace App\Actions\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RecordAuditLog
{
    /**
     * Append an audit log entry. Never mutates the auditable record.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        string $action,
        ?Model $auditable = null,
        ?User $actor = null,
        array $metadata = [],
        ?Carbon $occurredAt = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'metadata' => $metadata,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }
}
