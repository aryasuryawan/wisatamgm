@props([
    'title',
    'value',
    'sub' => null,
    'delta' => null,      // float persen atau null
    'deltaLabel' => null, // mis. "vs bulan lalu"
    'spark' => null,      // array angka untuk sparkline
    'anchor' => null,     // id target scroll
    'goodWhenUp' => true,
    'color' => null,      // text-success / text-danger override
])

@php
    $valueColor = $color;
    if (! $valueColor && $delta !== null) {
        $up = $delta >= 0;
        $good = $goodWhenUp ? $up : ! $up;
        $valueColor = $good ? 'text-success' : 'text-danger';
    }

    // Sparkline SVG server-side (tanpa library).
    $points = '';
    if ($spark && count($spark) > 1) {
        $max = max($spark);
        $min = min($spark);
        $span = max(0.0001, $max - $min);
        $w = 100;
        $h = 28;
        $step = $w / (count($spark) - 1);
        $pts = [];
        foreach ($spark as $i => $v) {
            $x = round($i * $step, 1);
            $y = round($h - (($v - $min) / $span) * ($h - 2) - 1, 1);
            $pts[] = "{$x},{$y}";
        }
        $points = implode(' ', $pts);
    }
@endphp

<div class="card" {{ $attributes->merge(['dusk' => 'kpi-card']) }}
     @if($anchor) style="cursor:pointer" onclick="document.getElementById('{{ $anchor }}')?.scrollIntoView({behavior:'smooth'})" @endif>
    <div class="card-body py-3">
        <div class="subheader">{{ $title }}</div>
        <div class="d-flex align-items-baseline gap-2">
            <div class="h2 mb-0 fw-bold {{ $valueColor }}" dusk="kpi-value">{{ $value }}</div>
        </div>

        @if ($delta !== null)
            <div class="mt-1 small">
                <span class="{{ $delta >= 0 ? 'text-success' : 'text-danger' }} fw-semibold" dusk="kpi-delta">
                    {{ $delta >= 0 ? '▲' : '▼' }} {{ abs($delta) }}%
                </span>
                @if ($deltaLabel)<span class="text-secondary">{{ $deltaLabel }}</span>@endif
            </div>
        @elseif ($sub)
            <div class="text-secondary small">{{ $sub }}</div>
        @endif

        @if ($points)
            <svg viewBox="0 0 100 28" preserveAspectRatio="none" class="mt-2 w-100" height="30"
                 style="overflow:visible" aria-hidden="true">
                <polyline points="{{ $points }}" fill="none" stroke="currentColor"
                          class="{{ $valueColor ?? 'text-primary' }}" stroke-width="1.6" vector-effect="non-scaling-stroke"/>
            </svg>
        @endif
    </div>
</div>
