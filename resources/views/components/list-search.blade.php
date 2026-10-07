@props(['action', 'label' => 'Search', 'value' => '', 'sortOptions' => [], 'sort' => '', 'direction' => 'asc'])

<x-card title="Search">
    <form method="GET" action="{{ $action }}">
        <div class="form-grid">
            <div class="form-field">
                <label class="form-label" for="search">{{ $label }}</label>
                <input class="form-control" id="search" name="search" value="{{ $value }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="sort">Sort by</label>
                <select class="form-control" id="sort" name="sort">
                    <option value="">Default order</option>
                    @foreach ($sortOptions as $key => $optionLabel)
                        <option value="{{ $key }}" @selected($sort === $key)>{{ $optionLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="direction">Direction</label>
                <select class="form-control" id="direction" name="direction">
                    <option value="asc" @selected($direction === 'asc')>Ascending</option>
                    <option value="desc" @selected($direction === 'desc')>Descending</option>
                </select>
            </div>
        </div>
        <div class="btn-row"><button type="submit" class="btn">Search</button></div>
    </form>
</x-card>
