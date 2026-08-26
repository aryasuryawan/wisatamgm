@props([
    'color' => 'secondary',
    'dusk' => null,
])

@php
// Kontras Tabler: .badge mewarisi warna teks parent, jadi wajib set eksplisit.
$lightColors = ['warning', 'info', 'light'];
$parts = explode(' ', $color);
$base = $parts[0];

$textClass = in_array('text-dark', $parts, true) || in_array('text-white', $parts, true)
    ? ''
    : (in_array($base, $lightColors, true) ? 'text-dark' : 'text-white');
@endphp

<span {{ $attributes->merge(['class' => 'badge bg-' . $color . ($textClass ? ' ' . $textClass : '')]) }}
      @if($dusk) dusk="{{ $dusk }}" @endif>
    {{ $slot }}
</span>
