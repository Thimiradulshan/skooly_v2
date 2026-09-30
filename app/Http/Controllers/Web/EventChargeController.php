<?php

namespace App\Http\Controllers\Web;

use App\Actions\Events\CreateEventCharge;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreEventChargeRequest;
use App\Models\Event;
use App\Models\Grade;

class EventChargeController extends Controller
{
    public function create(Event $event)
    {
        $event->load('charges.grade');

        return view('events.charges.create', [
            'event' => $event,
            'grades' => Grade::query()->orderBy('sequence_order')->get(),
        ]);
    }

    public function store(StoreEventChargeRequest $request, Event $event, CreateEventCharge $createEventCharge)
    {
        $createEventCharge->handle(
            $event,
            Grade::query()->findOrFail($request->integer('grade_id')),
            (string) $request->input('amount'),
        );

        return redirect()
            ->route('events.show', $event)
            ->with('status', 'Event charge created.');
    }
}
