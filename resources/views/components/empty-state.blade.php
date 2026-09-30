@props([
    'title' => 'Nothing here yet',
    'description' => null,
])

<div {{ $attributes->class(['empty-state']) }} role="status">
    <span class="empty-state-title">{{ $title }}</span>
    @if ($description)
        <span>{{ $description }}</span>
    @endif
</div>
