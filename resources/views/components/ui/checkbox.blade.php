@props(['name', 'label', 'checked' => false])

<div class="form-check form-check-lg">
    <input class="form-check-input" type="checkbox" id="{{ $name }}" name="{{ $name }}"
           value="1" @checked(old($name, $checked)) dusk="checkbox-{{ $name }}">
    <label class="form-check-label" for="{{ $name }}">{{ $label }}</label>
</div>
