@props([
    'id',
    'title',
    'subtitle' => null,
    'config',       // array config Chart.js (di-encode ke data-chart)
    'height' => '300px',
])

<div class="card" id="{{ $id }}" {{ $attributes->merge(['dusk' => 'chart-panel']) }}>
    <div class="card-header">
        <div>
            <h3 class="card-title">{{ $title }}</h3>
            @if ($subtitle)<div class="text-secondary small">{{ $subtitle }}</div>@endif
        </div>
    </div>
    <div class="card-body" style="height: {{ $height }}; position: relative;">
        <canvas x-data="chartPanel" data-chart="{{ json_encode($config) }}"></canvas>
    </div>
</div>
