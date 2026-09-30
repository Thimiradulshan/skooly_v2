@extends('layouts.app')

@section('title', 'Event participation')
@section('heading', 'Event participation for '.$event->name)

@section('content')
    <p>
        {{ $event->is_mandatory ? 'This event is mandatory; participation records do not change mandatory due generation.' : 'This event is opt-in; only opted-in eligible students receive event dues.' }}
    </p>

    @if ($students->isEmpty())
        <p>No students are enrolled in this event's academic year and charge grades.</p>
    @else
        <form method="POST" action="{{ route('events.participation.store', $event) }}">
            @csrf
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="opted_in" {{ old('status', 'opted_in') === 'opted_in' ? 'selected' : '' }}>Opted in</option>
                <option value="opted_out" {{ old('status') === 'opted_out' ? 'selected' : '' }}>Opted out</option>
            </select>

            <h2>Students</h2>
            @foreach ($students as $student)
                <label for="student_{{ $student->id }}">
                    <input type="checkbox" id="student_{{ $student->id }}" name="student_ids[]" value="{{ $student->id }}"
                        {{ in_array($student->id, (array) old('student_ids', [])) ? 'checked' : '' }}>
                    {{ $student->name }} ({{ $student->admission_no }})
                </label>
            @endforeach

            <p><button type="submit">Save participation</button> <a href="{{ route('events.show', $event) }}">Cancel</a></p>
        </form>
    @endif
@endsection
