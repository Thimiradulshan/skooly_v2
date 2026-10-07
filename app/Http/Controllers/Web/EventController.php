<?php

namespace App\Http\Controllers\Web;

use App\Actions\Events\CreateEvent;
use App\Actions\Events\UpdateEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ListSearchRequest;
use App\Http\Requests\Web\StoreEventRequest;
use App\Http\Requests\Web\UpdateEventRequest;
use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\FeeCategory;

class EventController extends Controller
{
    public function index(ListSearchRequest $request)
    {
        $search = $request->string('search')->trim()->toString();

        return view('events.index', [
            'events' => Event::query()
                ->with(['academicYear', 'feeCategory'])
                ->withCount(['charges', 'participations', 'eventDueItems'])
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhereHas('academicYear', fn ($academicYear) => $academicYear->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('feeCategory', fn ($feeCategory) => $feeCategory->where('name', 'like', "%{$search}%"));
                })
                ->orderBy('id')
                ->paginate(20)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create()
    {
        return view('events.create', $this->formData());
    }

    public function store(StoreEventRequest $request, CreateEvent $createEvent)
    {
        $event = $createEvent->handle(
            AcademicYear::query()->findOrFail($request->integer('academic_year_id')),
            FeeCategory::query()->findOrFail($request->integer('fee_category_id')),
            $this->eventData($request),
        );

        return redirect()
            ->route('events.show', $event)
            ->with('status', 'Event created.');
    }

    public function show(Event $event)
    {
        $event->load(['academicYear', 'feeCategory', 'charges.grade', 'participations.student']);
        $event->loadCount('eventDueItems');

        return view('events.show', ['event' => $event]);
    }

    public function edit(Event $event)
    {
        return view('events.edit', array_merge(['event' => $event], $this->formData()));
    }

    public function update(UpdateEventRequest $request, Event $event, UpdateEvent $updateEvent)
    {
        $updateEvent->handle(
            $event,
            AcademicYear::query()->findOrFail($request->integer('academic_year_id')),
            FeeCategory::query()->findOrFail($request->integer('fee_category_id')),
            $this->eventData($request),
        );

        return redirect()
            ->route('events.show', $event)
            ->with('status', 'Event updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'academicYears' => AcademicYear::query()->orderBy('id')->get(),
            'feeCategories' => FeeCategory::query()->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function eventData(StoreEventRequest|UpdateEventRequest $request): array
    {
        return [
            'name' => $request->string('name')->toString(),
            'event_date' => $request->string('event_date')->toString(),
            'description' => $request->input('description'),
            'is_mandatory' => $request->boolean('is_mandatory'),
        ];
    }
}
