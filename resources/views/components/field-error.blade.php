@props(['name'])

@error($name)
    <span {{ $attributes->class(['field-error']) }} id="{{ $name }}-error" role="alert">{{ $message }}</span>
@enderror
