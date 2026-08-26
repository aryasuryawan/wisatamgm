@props([
    'type' => 'info',
    'message' => null,
])

@if (! empty(trim((string) $message)))
    <div {{ $attributes->merge(['class' => 'alert alert-'.$type.' alert-dismissible fade show']) }} role="alert" dusk="alert-{{ $type }}">
        {{ $message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif
