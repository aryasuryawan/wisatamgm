<x-layouts.app>
    <x-slot:title>{{ __('ui.dashboard') }}</x-slot>
    <x-slot:pretitle>{{ config('app.name') }}</x-slot:pretitle>

    <x-slot:page_actions>
        <span class="text-secondary small d-none d-md-inline">
            <i class="ti ti-clock icon icon-1 me-1"></i>{{ __('ui.last_updated') }}: {{ now()->translatedFormat('d M H:i') }}
        </span>
    </x-slot:page_actions>

    {{-- Filter periode: segmented control --}}
    <div class="row mb-4" dusk="dashboard-period-filter">
        <div class="col-auto">
            <div class="sg-segmented" role="group" aria-label="{{ __('ui.period') }}">
                @php
                    $periodIcons = ['month' => 'ti-calendar', 'last_month' => 'ti-calendar-event', 'year' => 'ti-calendar-stats'];
                @endphp
                @foreach (['month', 'last_month', 'year'] as $p)
                    <a href="{{ route('dashboard', ['period' => $p]) }}"
                       class="sg-seg-btn {{ $period === $p ? 'active' : '' }}"
                       dusk="seg-period-{{ $p }}">
                        <i class="ti {{ $periodIcons[$p] }} sg-seg-icon"></i>
                        {{ __('ui.period_' . $p) }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Ringkasan periode terpilih --}}
    <div class="row row-deck row-cards mb-4">
        {{-- Omzet Hari Ini --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card sg-kpi-card" dusk="card-revenue-today">
                <div class="card-body py-3">
                    <div class="sg-card-icon sg-icon-today">
                        <i class="ti ti-cash"></i>
                    </div>
                    <div class="sg-kpi-content">
                        <div class="subheader">{{ __('ui.revenue_today') }}</div>
                        <div class="h2 mb-1 fw-bold sg-currency {{ $revenueToday == 0 ? 'sg-currency-zero' : '' }}">
                            Rp {{ number_format($revenueToday, 0, ',', '.') }}
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="text-secondary small">{{ __('ui.paid_transactions') }}: {{ $transactionsToday }}</div>
                            @if ($deltaToday !== null)
                                <span class="sg-delta {{ $deltaToday >= 0 ? 'sg-delta-up' : 'sg-delta-down' }}">
                                    {{ $deltaToday >= 0 ? '▲' : '▼' }} {{ abs($deltaToday) }}%
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Omzet Periode --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card sg-kpi-card" dusk="card-revenue-period">
                <div class="card-body py-3">
                    <div class="sg-card-icon sg-icon-revenue">
                        <i class="ti ti-chart-line"></i>
                    </div>
                    <div class="sg-kpi-content">
                        <div class="subheader">{{ __('ui.revenue') }} — {{ __('ui.period_' . $period) }}</div>
                        <div class="h2 mb-1 fw-bold sg-currency text-success {{ $pl['revenue'] == 0 ? 'sg-currency-zero' : '' }}">
                            Rp {{ number_format($pl['revenue'], 0, ',', '.') }}
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="text-secondary small">{{ __('ui.paid_transactions') }}: {{ $periodTransactions }}</div>
                            @if ($deltaRevenue !== null)
                                <span class="sg-delta {{ $deltaRevenue >= 0 ? 'sg-delta-up' : 'sg-delta-down' }}">
                                    {{ $deltaRevenue >= 0 ? '▲' : '▼' }} {{ abs($deltaRevenue) }}%
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Biaya Periode --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card sg-kpi-card" dusk="card-expense-period">
                <div class="card-body py-3">
                    <div class="sg-card-icon sg-icon-expense">
                        <i class="ti ti-receipt"></i>
                    </div>
                    <div class="sg-kpi-content">
                        <div class="subheader">{{ __('ui.expenses') }} — {{ __('ui.period_' . $period) }}</div>
                        <div class="h2 mb-1 fw-bold sg-currency text-danger {{ $pl['expense'] == 0 ? 'sg-currency-zero' : '' }}">
                            Rp {{ number_format($pl['expense'], 0, ',', '.') }}
                        </div>
                        @if ($deltaExpense !== null)
                            <div class="mt-1">
                                <span class="sg-delta {{ $deltaExpense <= 0 ? 'sg-delta-up' : 'sg-delta-down' }}">
                                    {{ $deltaExpense >= 0 ? '▲' : '▼' }} {{ abs($deltaExpense) }}%
                                </span>
                                <span class="text-secondary small">{{ __('ui.vs_previous') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Estimasi Laba --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card sg-kpi-card" dusk="card-profit-period">
                <div class="card-body py-3">
                    <div class="sg-card-icon sg-icon-profit">
                        <i class="ti ti-pig-money"></i>
                    </div>
                    <div class="sg-kpi-content">
                        <div class="subheader">{{ __('ui.profit_estimate') }}</div>
                        <div class="h2 mb-1 fw-bold sg-currency {{ $pl['profit'] >= 0 ? 'text-success' : 'text-danger' }} {{ $pl['profit'] == 0 ? 'sg-currency-zero' : '' }}">
                            Rp {{ number_format($pl['profit'], 0, ',', '.') }}
                        </div>
                        @if ($deltaProfit !== null)
                            <div class="mt-1">
                                <span class="sg-delta {{ $deltaProfit >= 0 ? 'sg-delta-up' : 'sg-delta-down' }}">
                                    {{ $deltaProfit >= 0 ? '▲' : '▼' }} {{ abs($deltaProfit) }}%
                                </span>
                                <span class="text-secondary small">{{ __('ui.vs_previous') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pembanding: tahun ini / bulan ini / bulan lalu --}}
    <div class="row row-deck row-cards mb-4" dusk="dashboard-period-comparison">
        <div class="col-sm-6 col-lg-4">
            <div class="card sg-compare-card sg-compare-year" dusk="card-compare-year">
                <div class="card-body py-3">
                    <div class="subheader">{{ __('ui.revenue_this_year') }}</div>
                    <div class="h2 mb-1 sg-currency">Rp {{ number_format($comparison['year']['revenue'], 0, ',', '.') }}</div>
                    <div class="text-secondary small">{{ __('ui.paid_transactions') }}: {{ $comparison['year']['transactions'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card sg-compare-card sg-compare-month" dusk="card-compare-month">
                <div class="card-body py-3">
                    <div class="subheader">{{ __('ui.revenue_this_month') }}</div>
                    <div class="h2 mb-1 sg-currency">Rp {{ number_format($comparison['month']['revenue'], 0, ',', '.') }}</div>
                    <div class="text-secondary small">{{ __('ui.paid_transactions') }}: {{ $comparison['month']['transactions'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card sg-compare-card sg-compare-lastmonth" dusk="card-compare-last-month">
                <div class="card-body py-3">
                    <div class="subheader">{{ __('ui.revenue_last_month') }}</div>
                    <div class="h2 mb-1 sg-currency">Rp {{ number_format($comparison['last_month']['revenue'], 0, ',', '.') }}</div>
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
                                @php
                                    $pct = round($row['revenue'] / $maxRevenue * 100);
                                    $barClass = match(true) {
                                        $pct >= 70 => 'sg-progress-high',
                                        $pct >= 40 => 'sg-progress-mid',
                                        default => 'sg-progress-low',
                                    };
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $row['branch']->name }}</td>
                                    <td style="min-width:140px">
                                        <div class="progress progress-sm">
                                            <div class="progress-bar {{ $barClass }}" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <span class="small text-secondary sg-currency">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</span>
                                    </td>
                                    <td class="text-end fw-semibold {{ $row['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        <span class="sg-currency">Rp {{ number_format($row['profit'], 0, ',', '.') }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('dashboard.branch', array_merge(request()->only(['preset', 'compare', 'date_from', 'date_until']), ['branch' => $row['branch']])) }}"
                                           class="btn btn-outline-primary btn-sm py-0 sg-drilldown-btn" dusk="drilldown-{{ $row['branch']->id }}"
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
