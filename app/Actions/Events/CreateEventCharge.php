<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\EventCharge;
use App\Models\Grade;
use Illuminate\Support\Facades\DB;

class CreateEventCharge
{
    /**
     * Create a per-grade event charge without generating due items.
     */
    public function handle(Event $event, Grade $grade, string $amount): EventCharge
    {
        return DB::transaction(fn (): EventCharge => $event->charges()->create([
            'grade_id' => $grade->id,
            'amount' => $amount,
        ]));
    }
}
