<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <label for="academic_year_id">Academic year</label>
    <select id="academic_year_id" name="academic_year_id" required>
        <option value="">-- choose --</option>
        @foreach ($academicYears as $academicYear)
            <option value="{{ $academicYear->id }}" {{ (int) old('academic_year_id', $event?->academic_year_id) === $academicYear->id ? 'selected' : '' }}>
                {{ $academicYear->name }}
            </option>
        @endforeach
    </select>

    <label for="fee_category_id">Fee category</label>
    <select id="fee_category_id" name="fee_category_id" required>
        <option value="">-- choose --</option>
        @foreach ($feeCategories as $feeCategory)
            <option value="{{ $feeCategory->id }}" {{ (int) old('fee_category_id', $event?->fee_category_id) === $feeCategory->id ? 'selected' : '' }}>
                {{ $feeCategory->name }}
            </option>
        @endforeach
    </select>

    <label for="name">Name</label>
    <input type="text" id="name" name="name" value="{{ old('name', $event?->name) }}" required>

    <label for="event_date">Event date</label>
    <input type="date" id="event_date" name="event_date" value="{{ old('event_date', $event?->event_date?->toDateString()) }}" required>

    <label for="description">Description</label>
    <textarea id="description" name="description" rows="3">{{ old('description', $event?->description) }}</textarea>

    <label for="is_mandatory">
        <input type="checkbox" id="is_mandatory" name="is_mandatory" value="1" {{ old('is_mandatory', $event?->is_mandatory ?? true) ? 'checked' : '' }}>
        Mandatory
    </label>

    <p><button type="submit">Save event</button> <a href="{{ route('events.index') }}">Cancel</a></p>
</form>
