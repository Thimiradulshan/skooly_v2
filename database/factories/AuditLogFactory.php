<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_user_id' => null,
            'action' => AuditLog::ACTION_FAMILY_CREATED,
            'auditable_type' => Family::class,
            'auditable_id' => Family::factory(),
            'metadata' => [],
            'occurred_at' => now(),
        ];
    }
}
