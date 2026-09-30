@props([
    'title' => null,
])

<div {{ $attributes->class(['card']) }}>
    @if ($title)
        <h2 class="card-title">{{ $title }}</h2>
    @endif
    {{ $slot }}
</div>
