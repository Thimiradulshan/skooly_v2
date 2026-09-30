@extends('layouts.app')

@section('title', 'Add event charge')

@section('content')
    <x-page-header :title="'Add a charge to '.$event->name" subtitle="One charge per grade and amount." />

    <x-card>
        <form method="POST" action="{{ route('events.charges.store', $event) }}">
            @csrf

            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="grade_id">Grade <span class="req">*</span></label>
                    <select class="form-control" id="grade_id" name="grade_id" required>
                        <option value="">-- choose --</option>
                        @foreach ($grades as $grade)
                            <option value="{{ $grade->id }}" {{ (int) old('grade_id') === $grade->id ? 'selected' : '' }}>
                                {{ $grade->name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="form-help">A grade can only be charged once per event.</span>
                </div>

                <div class="form-field">
                    <label class="form-label" for="amount">Amount <span class="req">*</span></label>
                    <input class="form-control" type="number" min="0" step="0.01" id="amount" name="amount"
                           value="{{ old('amount') }}" required>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn">Add charge</button>
                <a class="btn btn-secondary" href="{{ route('events.show', $event) }}">Cancel</a>
            </div>
        </form>
    </x-card>

    @if ($event->charges->isNotEmpty())
        <x-card title="Existing charges">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Grade</th><th class="num">Amount</th></tr></thead>
                    <tbody>
                    @foreach ($event->charges as $charge)
                        <tr>
                            <td>{{ $charge->grade->name }}</td>
                            <td class="num">{{ $charge->amount }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
@endsection
