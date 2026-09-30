@extends('layouts.app')

@section('title', 'Add event charge')
@section('heading', 'Add event charge for '.$event->name)

@section('content')
    <p>Charges are create-only in this web phase. Existing generated due items are never rewritten.</p>

    <form method="POST" action="{{ route('events.charges.store', $event) }}">
        @csrf
        <label for="grade_id">Grade</label>
        <select id="grade_id" name="grade_id" required>
            <option value="">-- choose --</option>
            @foreach ($grades as $grade)
                <option value="{{ $grade->id }}" {{ (int) old('grade_id') === $grade->id ? 'selected' : '' }}>
                    {{ $grade->name }}
                </option>
            @endforeach
        </select>

        <label for="amount">Amount</label>
        <input type="number" min="0" step="0.01" id="amount" name="amount" value="{{ old('amount') }}" required>

        <p><button type="submit">Add charge</button> <a href="{{ route('events.show', $event) }}">Cancel</a></p>
    </form>

    @if ($event->charges->isNotEmpty())
        <h2>Existing charges</h2>
        <table>
            @foreach ($event->charges as $charge)
                <tr><td>{{ $charge->grade->name }}</td><td>{{ $charge->amount }}</td></tr>
            @endforeach
        </table>
    @endif
@endsection
