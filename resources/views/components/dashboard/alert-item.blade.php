@props([
    'level' => 'warning', // danger | warning
    'title',
    'count',
    'duskId' => null,
])

<details class="card mb-2" {{ $attributes->merge(['dusk' => 'alert-item']) }}>
    <summary class="card-body py-2 d-flex align-items-center" style="cursor:pointer; list-style:none">
        <span class="badge bg-{{ $level === 'danger' ? 'danger' : 'warning text-dark' }} me-2">{{ $count }}</span>
        <span class="fw-semibold flex-fill">{{ $title }}</span>
        <i class="ti ti-chevron-down icon icon-1 text-secondary"></i>
    </summary>
    <div class="card-body pt-0 border-top" dusk="alert-detail">
        {{ $slot }}
    </div>
</details>
