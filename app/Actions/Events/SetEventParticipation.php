<?php

namespace App\Actions\Events;

use App\Academic\ActiveAcademicYear;
use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\EventParticipation;
use Illuminate\Support\Facades\DB;

class SetEventParticipation
{
    /**
     * Set the participation status for selected Students without generating due items.
     *
     * @param  array<int, int>  $studentIds
     */
    public function handle(Event $event, array $studentIds, string $status): int
    {
        (new ActiveAcademicYear)->ensure(AcademicYear::query()->findOrFail($event->academic_year_id));

        return DB::transaction(function () use ($event, $studentIds, $status): int {
            foreach ($studentIds as $studentId) {
                EventParticipation::query()->updateOrCreate(
                    ['event_id' => $event->id, 'student_id' => $studentId],
                    ['status' => $status],
                );
            }

            return count($studentIds);
        });
    }
}
