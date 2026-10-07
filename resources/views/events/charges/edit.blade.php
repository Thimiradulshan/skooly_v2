@extends('layouts.app')

@section('title', 'Edit event charge')

@section('content')
    <x-page-header :title="'Edit charge for '.$event->name" subtitle="Only the amount may change before event due generation." />

    <x-card>
        <dl class="kv">
            <div class="kv-row"><dt>Grade</dt><dd>{{ $charge->grade->name }}</dd></div>
        </dl>
    </x-card>

    @if ($isLocked)
        <div class="callout">
            <span class="callout-mark" aria-hidden="true">i</span>
            <p>This charge is locked because the event has generated due items. Those stored snapshots cannot be changed.</p>
        </div>
    @else
        <x-card>
            <form method="POST" action="{{ route('events.charges.update', [$event, $charge]) }}" data-loading>
                @csrf
                @method('PUT')

                <div class="form-field">
                    <label class="form-label" for="amount">Amount <span class="req">*</span></label>
                    <input class="form-control" type="number" min="0" step="0.01" id="amount" name="amount" value="{{ old('amount', $charge->amount) }}" required>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn">Update event charge</button>
                    <a class="btn btn-secondary" href="{{ route('events.show', $event) }}">Cancel</a>
                </div>
            </form>
        </x-card>
    @endif
@endsection
