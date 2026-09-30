@extends('layouts.app')

@section('title', 'Event participation')

@section('content')
    <x-page-header :title="'Participation for '.$event->name"
                   subtitle="Only students enrolled in the event's year and charge grades are listed." />

    @if ($students->isEmpty())
        <x-empty-state title="No eligible students"
                       description="No students are enrolled in this event's academic year and charge grades." />

        <div class="page-actions">
            <a class="btn btn-secondary" href="{{ route('events.show', $event) }}">Back to event</a>
        </div>
    @else
        @if ($event->is_mandatory)
            <x-alert type="info" title="This event is mandatory.">
                Participation records do not change which students are charged. Every enrolled student is charged.
            </x-alert>
        @else
            <p class="note">This event is opt-in. Only opted-in students are charged when dues are generated.</p>
        @endif

        <x-card>
            <form method="POST" action="{{ route('events.participation.store', $event) }}">
                @csrf

                <div class="form-field">
                    <label class="form-label" for="status">Status <span class="req">*</span></label>
                    <select class="form-control" id="status" name="status" required>
                        <option value="opted_in" {{ old('status', 'opted_in') === 'opted_in' ? 'selected' : '' }}>Opted in</option>
                        <option value="opted_out" {{ old('status') === 'opted_out' ? 'selected' : '' }}>Opted out</option>
                    </select>
                </div>

                <h2 class="section-heading">Students</h2>
                <div class="choice-list">
                    @foreach ($students as $student)
                        <div class="checkbox-field">
                            <input type="checkbox" id="student_{{ $student->id }}" name="student_ids[]" value="{{ $student->id }}"
                                   {{ in_array($student->id, (array) old('student_ids', [])) ? 'checked' : '' }}>
                            <label for="student_{{ $student->id }}">{{ $student->name }} ({{ $student->admission_no }})</label>
                        </div>
                    @endforeach
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn">Save participation</button>
                    <a class="btn btn-secondary" href="{{ route('events.show', $event) }}">Cancel</a>
                </div>
            </form>
        </x-card>
    @endif
@endsection
