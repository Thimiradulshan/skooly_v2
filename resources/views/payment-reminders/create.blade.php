@extends('layouts.app')

@section('title', 'Generate payment reminders')
@section('heading', 'Generate payment reminders')

@section('content')
    <form method="POST" action="{{ route('payment-reminders.store') }}">
        @csrf

        <label for="as_of_date">As of date</label>
        <input type="date" id="as_of_date" name="as_of_date" value="{{ old('as_of_date', now()->toDateString()) }}" required>

        <label for="upcoming_window_days">Upcoming window days</label>
        <input type="number" min="0" id="upcoming_window_days" name="upcoming_window_days" value="{{ old('upcoming_window_days', 7) }}">

        <label for="academic_year_id">Academic year (optional)</label>
        <select id="academic_year_id" name="academic_year_id">
            <option value="">-- all --</option>
            @foreach ($academicYears as $academicYear)
                <option value="{{ $academicYear->id }}" {{ (int) old('academic_year_id') === $academicYear->id ? 'selected' : '' }}>
                    {{ $academicYear->name }}
                </option>
            @endforeach
        </select>

        <label for="family_id">Family (optional)</label>
        <select id="family_id" name="family_id">
            <option value="">-- all --</option>
            @foreach ($families as $family)
                <option value="{{ $family->id }}" {{ (int) old('family_id') === $family->id ? 'selected' : '' }}>
                    {{ $family->family_code }}
                </option>
            @endforeach
        </select>

        <p><button type="submit">Generate reminder records</button> <a href="{{ route('payment-reminders.index') }}">Cancel</a></p>
    </form>

    <p>This creates internal upcoming and overdue reminder records only. It does not send any message or change payment data.</p>
@endsection
