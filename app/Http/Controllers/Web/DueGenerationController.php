<?php

namespace App\Http\Controllers\Web;

use App\Actions\Events\GenerateEventDueItems;
use App\Actions\Fees\GenerateRecurringDueItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\GenerateEventDuesRequest;
use App\Http\Requests\Web\GenerateRecurringDuesRequest;
use App\Models\AcademicYear;
use App\Models\Event;

class DueGenerationController extends Controller
{
    public function recurringCreate()
    {
        return view('due-generation.recurring', [
            'academicYears' => AcademicYear::query()->orderBy('id')->get(),
        ]);
    }

    public function recurringStore(GenerateRecurringDuesRequest $request, GenerateRecurringDueItems $generateRecurringDueItems)
    {
        $created = $generateRecurringDueItems->handle(
            AcademicYear::query()->findOrFail($request->integer('academic_year_id')),
            $request->string('due_date')->toString(),
            $request->input('cycle_key'),
            $request->user(),
        );

        return redirect()
            ->route('due-generation.recurring.create')
            ->with('status', "Recurring dues generated. {$created} due item(s) created.");
    }

    public function eventCreate()
    {
        return view('due-generation.events', [
            'events' => Event::query()->with('feeCategory')->orderBy('id')->get(),
        ]);
    }

    public function eventStore(GenerateEventDuesRequest $request, GenerateEventDueItems $generateEventDueItems)
    {
        $created = $generateEventDueItems->handle(
            Event::query()->findOrFail($request->integer('event_id')),
            $request->user(),
        );

        return redirect()
            ->route('due-generation.events.create')
            ->with('status', "Event dues generated. {$created} due item(s) created.");
    }
}
