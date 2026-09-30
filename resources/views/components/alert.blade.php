@props([
    'type' => 'info',
    'title' => null,
])

<div {{ $attributes->class(['alert', 'alert-'.($type === 'success' ? 'success' : ($type === 'error' ? 'error' : 'info'))]) }}
    @if ($title) role="alert" @endif>
    @if ($title)
        <strong>{{ $title }}</strong>
    @endif
    {{ $slot }}
</div>
