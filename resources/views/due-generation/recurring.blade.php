@extends('layouts.app')

@section('title', 'Recurring due generation')
@section('heading', 'Recurring due generation')

@section('content')
    <form method="POST" action="{{ route('due-generation.recurring.store') }}">
        @csrf

        <label for="academic_year_id">Academic year</label>
        <select id="academic_year_id" name="academic_year_id" required>
            <option value="">-- choose --</option>
            @foreach ($academicYears as $academicYear)
                <option value="{{ $academicYear->id }}"
                    {{ (int) old('academic_year_id') === $academicYear->id ? 'selected' : '' }}>
                    {{ $academicYear->name }}
                </option>
            @endforeach
        </select>

        <label for="due_date">Due date</label>
        <input type="date" id="due_date" name="due_date" value="{{ old('due_date', now()->toDateString()) }}" required>

        <label for="cycle_key">Cycle key (optional)</label>
        <input type="text" id="cycle_key" name="cycle_key" value="{{ old('cycle_key') }}">
        <p>Leave blank to use the due date month as the cycle key.</p>

        <p>
            <button type="submit">Generate recurring dues</button>
            <a href="{{ route('dues-dashboard.index') }}">View dashboard</a>
        </p>
    </form>

    <p>
        Dues are generated from recurring fee structures for the chosen academic year.
        Running this again for the same cycle is safe and will not duplicate due items.
    </p>
@endsection
