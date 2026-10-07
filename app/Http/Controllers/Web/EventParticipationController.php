<?php

namespace App\Http\Controllers\Web;

use App\Actions\Events\SetEventParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreEventParticipationRequest;
use App\Models\Event;
use App\Models\Student;
use InvalidArgumentException;

class EventParticipationController extends Controller
{
    public function create(Event $event)
    {
        $event->load('charges');

        return view('events.participation.create', [
            'event' => $event,
            'students' => Student::query()
                ->whereHas('enrollments', fn ($enrollments) => $enrollments
                    ->where('academic_year_id', $event->academic_year_id)
                    ->whereIn('grade_id', $event->charges->pluck('grade_id')))
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(StoreEventParticipationRequest $request, Event $event, SetEventParticipation $setEventParticipation)
    {
        try {
            $updated = $setEventParticipation->handle(
                $event,
                array_map('intval', $request->input('student_ids')),
                $request->string('status')->toString(),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['event_participation' => $exception->getMessage()]);
        }

        return redirect()
            ->route('events.show', $event)
            ->with('status', "Event participation updated for {$updated} student(s).");
    }
}
