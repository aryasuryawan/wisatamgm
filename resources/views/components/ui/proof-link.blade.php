@props([
    'path',
    'preview' => false,
    'size' => 'sm',
])

@if ($path)
    @php $url = \Storage::url($path); @endphp
    @if (str_ends_with($path, '.pdf'))
        <a href="{{ $url }}" target="_blank" class="btn btn-outline-primary btn-{{ $size }}" {{ $attributes->merge(['dusk' => 'proof-link']) }}>
            <i class="ti ti-file-text icon icon-1"></i> {{ __('ui.view_pdf') }}
        </a>
    @else
        <a href="{{ $url }}" target="_blank" class="text-decoration-none" {{ $attributes->merge(['dusk' => 'proof-link']) }}>
            <i class="ti ti-photo icon icon-1"></i> {{ __('ui.view_proof') }}
        </a>
        @if ($preview)
            <div class="mt-2">
                <a href="{{ $url }}" target="_blank">
                    <img src="{{ $url }}" alt="{{ __('ui.proof_attachment') }}" class="img-fluid rounded" style="max-height: 300px;">
                </a>
            </div>
        @endif
    @endif
@endif
