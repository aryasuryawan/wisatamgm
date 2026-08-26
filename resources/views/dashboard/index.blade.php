<x-layouts.app>
    <x-slot:title>{{ __('ui.dashboard') }}</x-slot>
    <x-slot:pretitle>{{ config('app.name') }}</x-slot:pretitle>

    {{-- Filter periode --}}
    <div class="row mb-3" dusk="dashboard-period-filter">
        <div class="col-auto">
            <div class="card">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <label class="form-label mb-0 fw-semibold">{{ __('ui.period') }}</label>
                    <select class="form-select form-select-sm w-auto" dusk="select-period"
                            onchange="window.location = '{{ route('dashboard') }}?period=' + this.value">
                        @foreach (['month', 'last_month', 'year'] as $p)
                            <option value="{{ $p }}" @selected($period === $p)>{{ __('ui.period_' . $p) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Ringkasan periode terpilih --}}
    <div class="row row-deck row-cards mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card" dusk="card-revenue-today">
                <div class="card-body py-3">
                    <div class="subheader">{{ __('ui.revenue_today') }}</div>
                    <div class="h2 mb-1 fw-bold">Rp {{ number_format($revenueToday, 0, ',', '.') }}</div>
                    <div class="text-secondary small">{{ __('ui.paid_transactions') }}: {{ $transactionsToday }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card" dusk="card-revenue-period">
                <div class="card-body py-3">
                    <div class="subheader">{{ __('ui.revenue') }} — {{ __('ui.period_' . $period) }}</div>
                    <div class="h2 mb-1 fw-bold text-success">Rp {{ number_format($pl['revenue'], 0, ',', '.') }}</div>
                    <div class="text-secondary small">{{ __('ui.paid_transactions') }}: {{ $periodTransactions }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card" dusk="card-expense-period">
                <div class="card-body py-3">
                    <div class="subheader">{{ __('ui.expenses') }} — {{ __('ui.period_' . $period) }}</div>
                    <div class="h2 mb-1 fw-bold text-danger">Rp {{ number_format($pl['expense'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card" dusk="card-profit-period">
                <div class="card-body py-3">
                    <div class="subheader">{{ __('ui.profit_estimate') }}</div>
                    <div class="h2 mb-1 fw-bold {{ $pl['profit'] >= 0 ? 'text-success' : 'text-danger' }}">Rp {{ number_format($pl['profit'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pembanding: tahun ini / bulan ini / bulan lalu --}}
    <div class="row row-deck row-cards mb-3" dusk="dashboard-period-comparison">
        <div class="col-sm-6 col-lg-4">
            <div class="card" dusk="card-compare-year">
                <div class="card-body py-3">
                    <div class="subheader">{{ __('ui.revenue_this_year') }}</div>
                    <div class="h2 mb-1">Rp {{ number_format($comparison['year']['revenue'], 0, ',', '.') }}</div>
                    <div class="text-secondary small">{{ __('ui.paid_transactions') }}: {{ $comparison['year']['transactions'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card" dusk="card-compare-month">
                <div class="card-body py-3">
                    <div class="subheader">{{ __('ui.revenue_this_month') }}</div>
                    <div class="h2 mb-1">Rp {{ number_format($comparison['month']['revenue'], 0, ',', '.') }}</div>
                    <div class="text-secondary small">{{ __('ui.paid_transactions') }}: {{ $comparison['month']['transactions'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card" dusk="card-compare-last-month">
                <div class="card-body py-3">
                    <div class="subheader">{{ __('ui.revenue_last_month') }}</div>
                    <div class="h2 mb-1">Rp {{ number_format($comparison['last_month']['revenue'], 0, ',', '.') }}</div>
                    <div class="text-secondary small">{{ __('ui.paid_transactions') }}: {{ $comparison['last_month']['transactions'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-deck row-cards">
        {{-- Perbandingan cabang (owner/konsolidasi) --}}
        @if (! empty($perBranch))
            <div class="col-lg-7">
                <x-ui.card :title="__('ui.branch_comparison_month')" :padded="false">
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter text-nowrap" dusk="dashboard-branch-table">
                            <thead>
                            <tr>
                                <th>{{ __('ui.table_branch') }}</th>
                                <th>{{ __('ui.revenue') }}</th>
                                <th class="text-end">{{ __('ui.profit_estimate') }}</th>
                                <th class="text-end"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $maxRevenue = collect($perBranch)->max('revenue') ?: 1;
                            @endphp
                            @forelse ($perBranch as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['branch']->name }}</td>
                                    <td style="min-width:140px">
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-primary" style="width: {{ round($row['revenue'] / $maxRevenue * 100) }}%"></div>
                                        </div>
                                        <span class="small text-secondary">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</span>
                                    </td>
                                    <td class="text-end fw-semibold {{ $row['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        Rp {{ number_format($row['profit'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('dashboard.branch', array_merge(request()->only(['preset', 'compare', 'date_from', 'date_until']), ['branch' => $row['branch']])) }}"
                                           class="btn btn-outline-primary btn-sm py-0" dusk="drilldown-{{ $row['branch']->id }}"
                                           title="{{ __('ui.detail') }}">{{ __('ui.detail') }} →</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">{{ __('ui.empty_report_data') }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        <a href="{{ route('reports.index') }}" class="btn btn-outline-primary btn-sm">{{ __('ui.reports') }} →</a>
                    </div>
                </x-ui.card>
            </div>
        @endif

        {{-- Alerts --}}
        <div class="col-lg-{{ empty($perBranch) ? 12 : 5 }}">
            <x-ui.card :title="__('ui.alerts')">
                <div class="list-group list-group-flush">
                    <a href="{{ route('inventory.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" dusk="alert-low-stock">
                        <span><i class="ti ti-alert-triangle icon icon-1 me-2 text-warning"></i> {{ __('ui.low_stock_products') }}</span>
                        <x-ui.badge color="{{ ($lowStockCount + $outOfStockCount) > 0 ? 'warning' : 'secondary' }}">{{ $lowStockCount + $outOfStockCount }}</x-ui.badge>
                    </a>

                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="ti ti-package-off icon icon-1 me-2 text-danger"></i> {{ __('ui.out_of_stock_products') }}</span>
                        <x-ui.badge color="{{ $outOfStockCount > 0 ? 'danger' : 'secondary' }}">{{ $outOfStockCount }}</x-ui.badge>
                    </div>

                    <a href="{{ route('schedules.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" dusk="alert-no-staff">
                        <span><i class="ti ti-calendar-x icon icon-1 me-2 text-danger"></i> {{ __('ui.schedules_without_guide') }}</span>
                        <x-ui.badge color="{{ $schedulesWithoutStaff->isNotEmpty() ? 'danger' : 'secondary' }}">{{ $schedulesWithoutStaff->count() }}</x-ui.badge>
                    </a>
                </div>

                @if ($schedulesWithoutStaff->isNotEmpty())
                    <div class="mt-3">
                        <div class="subheader mb-2">{{ __('ui.next_7_days') }}</div>
                        @foreach ($schedulesWithoutStaff as $schedule)
                            <div class="d-flex justify-content-between border-bottom pb-1 mb-1 small">
                                <span>{{ $schedule->product?->name }}</span>
                                <span class="text-secondary">{{ $schedule->date_start->translatedFormat('d M H:i') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>

        {{-- Info user --}}
        <div class="col-lg-12">
            <x-ui.card :title="__('ui.my_branches')">
                <div class="d-flex gap-2 flex-wrap">
                    @forelse ($myBranches as $branch)
                        <x-ui.badge color="primary">{{ $branch->name }} · {{ $branch->brand }}</x-ui.badge>
                    @empty
                        <span class="text-muted">{{ __('ui.no_branch_assigned') }}</span>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
