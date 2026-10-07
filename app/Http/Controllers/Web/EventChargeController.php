<?php

namespace App\Http\Controllers\Web;

use App\Actions\Events\CreateEventCharge;
use App\Actions\Events\UpdateEventCharge;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreEventChargeRequest;
use App\Http\Requests\Web\UpdateEventChargeRequest;
use App\Models\Event;
use App\Models\EventCharge;
use App\Models\Grade;
use RuntimeException;

class EventChargeController extends Controller
{
    public function create(Event $event)
    {
        $event->load('charges.grade');

        return view('events.charges.create', [
            'event' => $event,
            'grades' => Grade::query()->active()->orderBy('sequence_order')->get(),
        ]);
    }

    public function store(StoreEventChargeRequest $request, Event $event, CreateEventCharge $createEventCharge)
    {
        $createEventCharge->handle(
            $event,
            Grade::query()->active()->findOrFail($request->integer('grade_id')),
            (string) $request->input('amount'),
        );

        return redirect()
            ->route('events.show', $event)
            ->with('status', 'Event charge created.');
    }

    public function edit(Event $event, EventCharge $charge)
    {
        $charge->load('grade');

        return view('events.charges.edit', [
            'event' => $event,
            'charge' => $charge,
            'isLocked' => $event->eventDueItems()->exists(),
        ]);
    }

    public function update(
        UpdateEventChargeRequest $request,
        Event $event,
        EventCharge $charge,
        UpdateEventCharge $updateEventCharge,
    ) {
        try {
            $updateEventCharge->handle($charge, (string) $request->input('amount'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['event_charge' => $exception->getMessage()]);
        }

        return redirect()
            ->route('events.show', $event)
            ->with('status', 'Event charge updated. Existing due item snapshots were not changed.');
    }
}
