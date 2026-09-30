@props([
    'title',
    'description' => null,
])

<section {{ $attributes->class(['form-section']) }}>
    <div class="form-section-head">
        <h2 class="form-section-title">{{ $title }}</h2>
        @if ($description)
            <p class="form-section-description">{{ $description }}</p>
        @endif
    </div>
    {{ $slot }}
</section>
