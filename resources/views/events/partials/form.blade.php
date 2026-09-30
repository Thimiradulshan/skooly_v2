<form method="POST" action="{{ $action }}" data-loading>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="form-grid">
        <div class="form-field">
            <label class="form-label" for="academic_year_id">Academic year <span class="req">*</span></label>
            <select class="form-control" id="academic_year_id" name="academic_year_id" required>
                <option value="">-- choose --</option>
                @foreach ($academicYears as $academicYear)
                    <option value="{{ $academicYear->id }}" {{ (int) old('academic_year_id', $event?->academic_year_id) === $academicYear->id ? 'selected' : '' }}>
                        {{ $academicYear->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-field">
            <label class="form-label" for="fee_category_id">Fee category <span class="req">*</span></label>
            <select class="form-control" id="fee_category_id" name="fee_category_id" required>
                <option value="">-- choose --</option>
                @foreach ($feeCategories as $feeCategory)
                    <option value="{{ $feeCategory->id }}" {{ (int) old('fee_category_id', $event?->fee_category_id) === $feeCategory->id ? 'selected' : '' }}>
                        {{ $feeCategory->name }}
                    </option>
                @endforeach
            </select>
            <span class="form-help">Event dues are charged under this category.</span>
        </div>

        <div class="form-field">
            <label class="form-label" for="name">Name <span class="req">*</span></label>
            <input class="form-control" type="text" id="name" name="name" value="{{ old('name', $event?->name) }}" required>
        </div>

        <div class="form-field">
            <label class="form-label" for="event_date">Event date <span class="req">*</span></label>
            <input class="form-control" type="date" id="event_date" name="event_date"
                   value="{{ old('event_date', $event?->event_date?->toDateString()) }}" required>
        </div>

        <div class="form-field form-field-full">
            <label class="form-label" for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $event?->description) }}</textarea>
        </div>
    </div>

    <div class="checkbox-field">
        <input type="checkbox" id="is_mandatory" name="is_mandatory" value="1"
               {{ old('is_mandatory', $event?->is_mandatory ?? true) ? 'checked' : '' }}>
        <label for="is_mandatory">Mandatory</label>
        <span class="form-help">Mandatory events charge every enrolled student. Opt-in events charge only students who opt in.</span>
    </div>

    <div class="btn-row">
        <button type="submit" class="btn">Save event</button>
        <a class="btn btn-secondary" href="{{ $event ? route('events.show', $event) : route('events.index') }}">Cancel</a>
    </div>
</form>
