@props([
    'title' => null,
    'actions' => null,
    'padded' => true,
])

<div {{ $attributes->merge(['class' => 'card shadow-sm', 'dusk' => 'card']) }}>
    @if ($padded)
        <div class="card-body p-4">
            @if ($title)
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0 fw-semibold">{{ $title }}</h5>
                    @if ($actions)
                        <div class="d-flex gap-2">{{ $actions }}</div>
                    @endif
                </div>
            @endif
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</div>
