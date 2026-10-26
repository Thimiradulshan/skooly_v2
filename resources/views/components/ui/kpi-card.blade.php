@props([
    'title',
    'value',
    'hint' => null,
    'icon' => 'users',
    'accent' => 'primary',
])

@php
    $accentClass = match ($accent) {
        'success' => 'ui-kpi--success',
        'warning' => 'ui-kpi--warning',
        'surface' => 'ui-kpi--surface',
        default => 'ui-kpi--primary',
    };
@endphp

<article {{ $attributes->class(['ui-kpi-card', $accentClass]) }}>
    <div>
        <p class="ui-kpi-label">{{ $title }}</p>
        <p class="ui-kpi-value">{{ $value }}</p>
        @if ($hint)
            <p class="ui-kpi-hint">{{ $hint }}</p>
        @endif
    </div>
    <span class="ui-kpi-icon" aria-hidden="true">
        @if ($icon === 'family')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3"/><path d="M3.5 20c.5-3.2 2.4-5 5.5-5s5 1.8 5.5 5M16.5 10.5a2.5 2.5 0 1 0-1.3-4.6M16 15.2c2.3.3 3.8 1.9 4.2 4.8"/></svg>
        @elseif ($icon === 'staff')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 20v-1.5A4.5 4.5 0 0 1 8.5 14h7a4.5 4.5 0 0 1 4.5 4.5V20"/><circle cx="12" cy="7" r="3.5"/><path d="M7 4.5 12 2l5 2.5L12 7z"/></svg>
        @elseif ($icon === 'dues')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h5M8 16h3"/><path d="M17 15.5v3M15.5 17h3"/></svg>
        @else
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path d="M4 21v-1.5A5.5 5.5 0 0 1 9.5 14h5a5.5 5.5 0 0 1 5.5 5.5V21"/></svg>
        @endif
    </span>
</article>
