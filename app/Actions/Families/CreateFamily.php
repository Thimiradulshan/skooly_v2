<?php

namespace App\Actions\Families;

use App\Actions\Audit\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateFamily
{
    /**
     * Create a Family and any Guardians supplied with it.
     *
     * Guardians are never linked to Students here; linking is an explicit separate step.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $guardians
     */
    public function handle(array $data, array $guardians = [], ?User $actor = null): Family
    {
        return DB::transaction(function () use ($data, $guardians, $actor): Family {
            $family = Family::query()->create([
                'family_code' => $data['family_code'],
                'address' => $data['address'] ?? null,
                'home_contact_no' => $data['home_contact_no'] ?? null,
                'combined_billing_enabled' => $data['combined_billing_enabled'] ?? true,
            ]);

            foreach ($guardians as $guardian) {
                $family->guardians()->create([
                    'name' => $guardian['name'],
                    'relationship' => $guardian['relationship'] ?? null,
                    'contact_no' => $guardian['contact_no'] ?? null,
                    'email' => $guardian['email'] ?? null,
                    'nic' => $guardian['nic'] ?? null,
                ]);
            }

            (new RecordAuditLog)->handle(AuditLog::ACTION_FAMILY_CREATED, $family, $actor, [
                'family_id' => $family->id,
                'family_code' => $family->family_code,
                'guardian_count' => $family->guardians()->count(),
            ]);

            return $family;
        });
    }
}
