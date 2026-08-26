<x-layouts.app>
    <x-slot:title>{{ $customer->name }}</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-semibold mb-0">{{ $customer->name }}</h1>
        <div class="d-flex gap-2">
            @can('customers.edit')
                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-primary" dusk="edit-customer">
                    {{ __('ui.edit') }}
                </a>
            @endcan
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">{{ __('ui.cancel') }}</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <x-ui.card :title="__('ui.name')">
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">{{ __('ui.phone') }}</p>
                        <p class="fw-semibold mb-0">{{ $customer->phone ?: '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">{{ __('ui.email') }}</p>
                        <p class="fw-semibold mb-0">{{ $customer->email ?: '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">{{ __('ui.source') }}</p>
                        <x-ui.badge color="info">{{ $customer->source }}</x-ui.badge>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">{{ __('ui.nationality') }}</p>
                        @if($customer->nationality_type === 'indonesia')
                            <x-ui.badge color="success">{{ __('ui.indonesia') }}</x-ui.badge>
                        @else
                            <x-ui.badge color="primary">{{ __('ui.international') }}</x-ui.badge>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">{{ __('ui.segment') }}</p>
                        @php $seg = $customer->segment; @endphp
                        <x-ui.badge :color="$seg === 'VIP' ? 'warning' : ($seg === 'Repeat' ? 'primary' : 'secondary')">
                            {{ $seg }}
                        </x-ui.badge>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">{{ __('ui.branch') }}</p>
                        <x-ui.badge color="secondary">{{ $customer->branch?->name ?? '-' }}</x-ui.badge>
                    </div>
                    <div class="col-12">
                        <p class="mb-1 text-muted">{{ __('ui.note') }}</p>
                        <p class="mb-0">{{ $customer->notes ?: '-' }}</p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card :title="__('ui.preferences')" class="mt-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">{{ __('ui.preferences_allergies') }}</p>
                        <p class="fw-semibold mb-0">{{ $customer->getPreference('allergies') ?: '-' }}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">{{ __('ui.preferences_equipment_size') }}</p>
                        <p class="fw-semibold mb-0">{{ $customer->getPreference('equipment_size') ?: '-' }}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">{{ __('ui.preferences_experience_level') }}</p>
                        <p class="fw-semibold mb-0">{{ $customer->getPreference('experience_level') ?: '-' }}</p>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <div class="col-12 col-lg-4">
            <x-ui.card :title="__('ui.stats')">
                <div class="row g-3 text-center">
                    <div class="col-6">
                        <p class="h3 fw-bold mb-0 text-primary">{{ $customer->total_orders }}</p>
                        <p class="text-muted mb-0">{{ __('ui.orders') }}</p>
                    </div>
                    <div class="col-6">
                        <p class="h3 fw-bold mb-0 text-success">Rp {{ number_format($customer->total_spent, 0, ',', '.') }}</p>
                        <p class="text-muted mb-0">{{ __('ui.total_spent') }}</p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card :title="__('ui.certifications')" class="mt-3">
                @forelse ($customer->certifications as $cert)
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <x-ui.badge color="secondary">{{ $cert->agency }}</x-ui.badge>
                                <span class="fw-semibold">{{ $cert->level }}</span>
                            </div>
                            <small class="text-muted">{{ $cert->cert_date?->format('d M Y') ?: '-' }}</small>
                        </div>
                        <small class="text-muted">{{ $cert->cert_number ?: '-' }}</small>
                        @if($cert->expiry_date)
                            <br><small class="text-danger">{{ __('ui.expires') }}: {{ $cert->expiry_date->format('d M Y') }}</small>
                        @endif
                    </div>
                @empty
                    <p class="text-muted mb-0">{{ __('ui.no_certifications') }}</p>
                @endforelse
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
