@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
])

@php
$error = ($errors ?? null)?->has($name);
@endphp

<div class="mb-3">
    <label for="{{ $name }}" class="form-label fw-semibold">
        {{ $label }}@if($required) <span class="text-danger">*</span>@endif
    </label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'form-control'.($error ? ' is-invalid' : '')]) }}
        dusk="input-{{ $name }}"
    >

    @if ($error)
        <div class="invalid-feedback">{{ $errors->first($name) }}</div>
    @endif
</div>
