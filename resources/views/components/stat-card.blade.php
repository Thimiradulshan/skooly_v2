@props([
    'label',
    'value',
    'hint' => null,
])

<div {{ $attributes->class(['metric']) }}>
    <span class="metric-label">{{ $label }}</span>
    <span class="metric-value">{{ $value }}</span>
    @if ($hint)
        <span class="metric-hint">{{ $hint }}</span>
    @endif
</div>
