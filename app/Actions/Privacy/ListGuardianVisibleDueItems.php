<?php

namespace App\Actions\Privacy;

use App\Models\Guardian;
use App\Models\StudentDueItem;
use Illuminate\Support\Collection;

class ListGuardianVisibleDueItems
{
    /**
     * List due items for explicitly linked Students only.
     *
     * Family membership and combined billing never widen this result.
     *
     * @return Collection<int, StudentDueItem>
     */
    public function handle(Guardian $guardian): Collection
    {
        $studentIds = $guardian->students()->pluck('students.id');

        return StudentDueItem::query()
            ->whereIn('student_id', $studentIds)
            ->orderBy('id')
            ->get();
    }
}
