@if (session('status'))
    <div data-flash-message="{{ session('status') }}" data-flash-tone="success" hidden></div>
@endif

@if (session('error'))
    <div data-flash-message="{{ session('error') }}" data-flash-tone="error" hidden></div>
@endif

@if ($errors->any())
    <x-alert type="error" title="Please correct the following:">
        <ul class="alert-list">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
