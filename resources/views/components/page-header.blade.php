@props([
    'title',
    'subtitle' => null,
    'eyebrow' => 'Workspace',
])

<div class="page-header">
    <div class="page-header-copy">
        <span class="page-eyebrow">{{ $eyebrow }}</span>
        <h1 class="page-header-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="page-header-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
</div>
