@props([
    'href',
    'variant' => 'primary',
    'size' => null,
])

<a {{ $attributes->class([
        'btn',
        'btn-secondary' => $variant === 'secondary',
        'btn-quiet' => $variant === 'quiet',
        'btn-small' => $size === 'small',
    ]) }} href="{{ $href }}">{{ $slot }}</a>
