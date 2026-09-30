<?php

namespace App\Actions\Events;

use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\FeeCategory;

class UpdateEvent
{
    /**
     * Update Event fields without modifying existing due item snapshots.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Event $event, AcademicYear $academicYear, FeeCategory $feeCategory, array $data): Event
    {
        $event->fill([
            'academic_year_id' => $academicYear->id,
            'fee_category_id' => $feeCategory->id,
            'name' => $data['name'],
            'event_date' => $data['event_date'],
            'description' => $data['description'] ?? null,
            'is_mandatory' => $data['is_mandatory'] ?? true,
        ])->save();

        return $event->refresh();
    }
}
