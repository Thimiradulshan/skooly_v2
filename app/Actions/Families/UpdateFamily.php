<?php

namespace App\Actions\Families;

use App\Actions\Audit\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateFamily
{
    /**
     * Only these Family fields may be changed through this workflow.
     *
     * @var array<int, string>
     */
    private const EDITABLE_FIELDS = [
        'family_code',
        'address',
        'home_contact_no',
        'combined_billing_enabled',
    ];

    /**
     * Update a Family without merging it into another Family.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Family $family, array $data, ?User $actor = null): Family
    {
        return DB::transaction(function () use ($family, $data, $actor): Family {
            $before = $family->only(self::EDITABLE_FIELDS);
            $family->fill(array_intersect_key($data, array_flip(self::EDITABLE_FIELDS)))->save();
            $after = $family->only(self::EDITABLE_FIELDS);

            (new RecordAuditLog)->handle(AuditLog::ACTION_FAMILY_UPDATED, $family, $actor, [
                'family_id' => $family->id,
                'before' => $before,
                'after' => $after,
            ]);

            return $family->refresh();
        });
    }
}
