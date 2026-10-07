<?php

namespace App\Actions\Academic;

use App\Actions\Audit\RecordAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SetAcademicRecordArchived
{
    public function handle(Model $record, bool $isArchived, string $auditAction, User $actor): void
    {
        $record->update(['is_archived' => $isArchived]);

        (new RecordAuditLog)->handle($auditAction, $record, $actor, [
            'record_type' => $record->getMorphClass(),
            'record_id' => $record->getKey(),
            'is_archived' => $isArchived,
        ]);
    }
}
