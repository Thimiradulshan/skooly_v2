@props([
    'value',
])

@php
    $tone = match (strtolower((string) $value)) {
        'paid', 'active', 'confirmed', 'applied', 'sent' => 'badge-ok',
        'partially_paid', 'pending', 'draft', 'upcoming', 'opted_in' => 'badge-warn',
        'cancelled', 'graduated', 'withdrawn', 'skipped', 'opted_out' => 'badge-danger',
        default => 'badge-info',
    };
@endphp

<span {{ $attributes->class(['badge', $tone]) }}>{{ $value }}</span>
