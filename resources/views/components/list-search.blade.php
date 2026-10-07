@props(['action', 'label' => 'Search', 'value' => ''])

<x-card title="Search">
    <form method="GET" action="{{ $action }}">
        <div class="form-grid">
            <div class="form-field">
                <label class="form-label" for="search">{{ $label }}</label>
                <input class="form-control" id="search" name="search" value="{{ $value }}">
            </div>
        </div>
        <div class="btn-row"><button type="submit" class="btn">Search</button></div>
    </form>
</x-card>
