@if (session('status'))
    <x-alert type="success">{{ session('status') }}</x-alert>
@endif

@if (session('error'))
    <x-alert type="error">{{ session('error') }}</x-alert>
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
