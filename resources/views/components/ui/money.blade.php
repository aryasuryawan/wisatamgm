@props([
    'name',
    'label',
    'value' => null,
    'required' => false,
    'dusk' => null,
])

@php
$error = ($errors ?? null)?->has($name);
$duskName = $dusk ?? 'input-' . $name;
@endphp

<div class="mb-3" x-data="moneyField">
    <label for="display-{{ $name }}" class="form-label fw-semibold">
        {{ $label }}@if($required) <span class="text-danger">*</span>@endif
    </label>

    <input type="hidden" name="{{ $name }}" value="{{ old($name, $value) }}" x-ref="hidden">

    <input id="display-{{ $name }}" type="text" inputmode="numeric" autocomplete="off"
           placeholder="0" x-ref="display" x-model="display"
           @input="onInput"
           @if($required) required @endif
           {{ $attributes->merge(['class' => 'form-control'.($error ? ' is-invalid' : '')]) }}
           dusk="{{ $duskName }}">

    @if ($error)
        <div class="invalid-feedback">{{ $errors->first($name) }}</div>
    @endif
</div>
