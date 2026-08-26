@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => null,
    'href' => null,
    'as' => null,
])

@php
$tag = $as ?? ($type === 'link' || $href ? 'a' : 'button');
$classes = 'btn btn-'.$variant;
if ($size) {
    $classes .= ' btn-'.$size;
}
@endphp

<{{ $tag }}
    {{ $attributes->merge(['class' => $classes]) }}
    @if ($tag === 'button') type="{{ $type }}" @endif
    @if ($href) href="{{ $href }}" @endif
>
    {{ $slot }}
</{{ $tag }}>
