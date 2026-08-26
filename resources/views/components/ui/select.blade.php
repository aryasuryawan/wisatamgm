@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'required' => false,
])

@php
$error = ($errors ?? null)?->has($name);
$selected = old($name, $value);
@endphp

<div class="mb-3">
    <label for="{{ $name }}" class="form-label fw-semibold">
        {{ $label }}@if($required) <span class="text-danger">*</span>@endif
    </label>

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'form-select'.($error ? ' is-invalid' : '')]) }}
        dusk="select-{{ $name }}"
    >
        @if (! is_null($placeholder))
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $optionValue === (string) $selected)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @if ($error)
        <div class="invalid-feedback">{{ $errors->first($name) }}</div>
    @endif
</div>
