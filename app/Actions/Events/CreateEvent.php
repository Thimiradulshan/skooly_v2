<?php

namespace App\Actions\Events;

use App\Academic\ActiveAcademicYear;
use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\FeeCategory;
use Illuminate\Support\Facades\DB;

class CreateEvent
{
    /**
     * Create an Event without generating any due items.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(AcademicYear $academicYear, FeeCategory $feeCategory, array $data): Event
    {
        (new ActiveAcademicYear)->ensure($academicYear);

        return DB::transaction(fn (): Event => Event::query()->create([
            'academic_year_id' => $academicYear->id,
            'fee_category_id' => $feeCategory->id,
            'name' => $data['name'],
            'event_date' => $data['event_date'],
            'description' => $data['description'] ?? null,
            'is_mandatory' => $data['is_mandatory'] ?? true,
        ]));
    }
}
