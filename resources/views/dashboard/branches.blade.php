<x-layouts.app>
    <x-slot:title>{{ __('ui.branch_dashboard_title') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.dashboard') }}</x-slot:pretitle>

    <div class="row row-deck row-cards" dusk="branch-picker">
        @forelse ($perBranch as $row)
            <div class="col-sm-6 col-lg-4">
                <div class="card" dusk="branch-card-{{ $row['branch']->id }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="subheader">{{ $row['branch']->brand }}</div>
                                <h3 class="card-title mb-0">{{ $row['branch']->name }}</h3>
                            </div>
                            <x-ui.badge color="{{ $row['branch']->is_active ? 'success' : 'secondary' }}">
                                {{ $row['branch']->is_active ? __('ui.branch_active') : __('ui.inactive') }}
                            </x-ui.badge>
                        </div>
                        <div class="d-flex justify-content-between small text-secondary mb-1">
                            <span>{{ __('ui.revenue_month') }}</span>
                            <span class="fw-semibold text-dark">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between small text-secondary mb-1">
                            <span>{{ __('ui.profit_estimate') }}</span>
                            <span class="fw-semibold {{ $row['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                Rp {{ number_format($row['profit'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <x-ui.badge color="{{ $row['profit'] >= 0 ? 'success' : 'danger' }}">
                                {{ $row['profit'] >= 0 ? 'Profit' : 'Rugi' }}
                            </x-ui.badge>
                            <x-ui.badge color="info">{{ $row['transactions'] }} {{ __('ui.transactions') }}</x-ui.badge>
                        </div>
                        <a href="{{ route('dashboard.branch', array_merge(request()->only(['preset', 'compare', 'date_from', 'date_until']), ['branch' => $row['branch']])) }}"
                           class="btn btn-primary w-100" dusk="open-branch-{{ $row['branch']->id }}">
                            {{ __('ui.open_branch_dashboard') }}
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><x-ui.alert type="info" message="{{ __('ui.empty_report_data') }}"/></div>
        @endforelse
    </div>
</x-layouts.app>
