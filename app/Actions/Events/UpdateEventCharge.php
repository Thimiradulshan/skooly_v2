<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\EventCharge;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UpdateEventCharge
{
    /**
     * Update an event charge before its event has produced any due items.
     */
    public function handle(EventCharge $eventCharge, string $amount): EventCharge
    {
        return DB::transaction(function () use ($eventCharge, $amount): EventCharge {
            $event = Event::query()->lockForUpdate()->findOrFail($eventCharge->event_id);
            $eventCharge = EventCharge::query()->lockForUpdate()->findOrFail($eventCharge->id);

            if ($event->eventDueItems()->exists()) {
                throw new RuntimeException('This event charge is locked because the event has generated due items.');
            }

            $eventCharge->update(['amount' => $amount]);

            return $eventCharge->refresh();
        });
    }
}
