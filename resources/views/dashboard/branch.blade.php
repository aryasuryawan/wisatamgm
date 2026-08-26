<x-layouts.app>
    <x-slot:title>{{ $branch->name }} — Dashboard</x-slot>
    <x-slot:pretitle>Dashboard Cabang</x-slot:pretitle>

    {{-- ---------------------------------------------------------- Filter --}}
    <div class="card mb-3" dusk="branch-filter">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center w-100">
                <div class="col-auto">
                    <div class="btn-list">
                        @foreach ([
                            'today' => 'Hari Ini', 'week' => 'Minggu Ini', 'month' => 'Bulan Ini',
                            'prev_month' => 'Bulan Lalu', 'custom' => 'Kustom',
                        ] as $key => $label)
                            <a href="{{ route('dashboard.branch', array_merge(request()->only(['compare', 'date_from', 'date_until']), ['branch' => $branch, 'preset' => $key])) }}"
                               class="btn btn-sm {{ request('preset', 'month') === $key ? 'btn-primary' : 'btn-outline-secondary' }}"
                               dusk="preset-{{ $key }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="col-auto {{ request('preset') === 'custom' ? '' : 'd-none' }} d-print-none">
                    <input type="date" name="date_from" value="{{ request('date_from', $from->format('Y-m-d')) }}" class="form-control form-control-sm">
                </div>
                <div class="col-auto {{ request('preset') === 'custom' ? '' : 'd-none' }} d-print-none">
                    <input type="date" name="date_until" value="{{ request('date_until', $until->format('Y-m-d')) }}" class="form-control form-control-sm">
                </div>

                {{-- Pertahankan rentang kustom saat preset bukan custom --}}
                @unless (request('preset') === 'custom')
                    <input type="hidden" name="date_from" value="{{ $from->format('Y-m-d') }}">
                    <input type="hidden" name="date_until" value="{{ $until->format('Y-m-d') }}">
                @endunless

                <div class="col-auto form-check ms-2">
                    <input class="form-check-input" type="checkbox" id="compareToggle" name="compare" value="1"
                           {{ $compare ? 'checked' : '' }} onchange="this.form.submit()" dusk="compare-toggle">
                    <label class="form-check-label small" for="compareToggle">Bandingkan periode sebelumnya</label>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- --------------------------------------------- Pindah cabang cepat --}}
    <div class="mb-3 d-flex align-items-center gap-2 flex-wrap">
        <span class="text-secondary small">Pindah cabang:</span>
        @foreach ($branches as $b)
            <a href="{{ route('dashboard.branch', array_merge(request()->only(['preset', 'compare', 'date_from', 'date_until']), ['branch' => $b])) }}"
               class="badge {{ $b->id === $branch->id ? 'bg-primary text-white' : 'bg-secondary-lt text-dark' }}"
               dusk="switch-branch-{{ $b->id }}">{{ $b->name }}</a>
        @endforeach
    </div>

    {{-- --------------------------------------------------------- KPI cards --}}
    <div class="row row-deck row-cards mb-3">
        <div class="col-sm-6 col-lg-3">
            <x-dashboard.kpi-card title="{{ __('ui.revenue_month') }}" anchor="chart-trend"
                                  value="Rp {{ number_format($pl['revenue'], 0, ',', '.') }}"
                                  sub="{{ __('ui.paid_transactions') }}: {{ $txnCount }}"
                                  :delta="$compare ? $deltas['revenue'] : null"
                                  deltaLabel="vs periode sblmnya"
                                  :spark="$sparkRevenue" color="text-success"/>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-dashboard.kpi-card title="{{ __('ui.expense_month') }}" anchor="chart-trend"
                                  value="Rp {{ number_format($pl['expense'], 0, ',', '.') }}"
                                  :delta="$compare ? $deltas['expense'] : null"
                                  deltaLabel="vs periode sblmnya"
                                  goodWhenUp="{{ false }}" color="text-danger"/>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-dashboard.kpi-card title="{{ __('ui.profit_estimate') }}" anchor="chart-trend"
                                  value="Rp {{ number_format($pl['profit'], 0, ',', '.') }}"
                                  :delta="$compare ? $deltas['profit'] : null"
                                  deltaLabel="vs periode sblmnya"/>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card" dusk="kpi-export">
                <div class="card-body py-3">
                    <div class="subheader">{{ __('ui.export_report') }}</div>
                    <div class="btn-list mt-2">
                        <a href="{{ route('reports.pdf', ['branch_id' => $branch->id, 'date_from' => $from->format('Y-m-d'), 'date_until' => $until->format('Y-m-d')]) }}"
                           target="_blank" class="btn btn-sm btn-primary" dusk="export-pdf">PDF</a>
                        <a href="{{ route('reports.export', ['branch_id' => $branch->id, 'date_from' => $from->format('Y-m-d'), 'date_until' => $until->format('Y-m-d')]) }}"
                           class="btn btn-sm btn-outline-secondary" dusk="export-csv">CSV</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ------------------------------------------------------------ Insight --}}
    @if (! empty($insights))
        <div class="card mb-3 border-{{ collect($insights)->first()['level'] }}" dusk="insight-panel">
            <div class="card-body py-2">
                <div class="subheader mb-1"><i class="ti ti-bulb icon icon-1 me-1"></i>{{ __('ui.insights') }}</div>
                @foreach ($insights as $insight)
                    <p class="mb-1 small">
                        <span class="badge bg-{{ $insight['level'] }} me-1">&nbsp;</span>{{ $insight['text'] }}
                    </p>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ------------------------------------------------------------- Charts --}}
    <div class="row row-deck row-cards mb-3">
        <div class="col-lg-8" id="chart-trend">
            <x-dashboard.chart-panel id="trend-panel" title="{{ __('ui.revenue_vs_expense_vs_profit') }}"
                                     subtitle="{{ $from->translatedFormat('d M Y') }} – {{ $until->translatedFormat('d M Y') }}"
                                     height="320px" :config="$trendChart" dusk-id="trend"/>
        </div>
        <div class="col-lg-4">
            <x-dashboard.chart-panel id="chart-category" title="{{ __('ui.sales_per_category') }}"
                                     height="320px" :config="$categoryChart"/>
        </div>
    </div>

    <div class="row row-deck row-cards mb-3">
        <div class="col-lg-6">
            <x-dashboard.chart-panel id="chart-day" title="{{ __('ui.busiest_day') }}" height="240px" :config="$dayChart"/>
        </div>
        <div class="col-lg-6">
            <x-dashboard.chart-panel id="chart-hour" title="{{ __('ui.busiest_hour') }}" height="240px" :config="$hourChart"/>
        </div>
    </div>

    <div class="row row-deck row-cards mb-3">
        <div class="col-lg-6">
            <x-ui.card :title="__('ui.top_products')" :padded="false">
                <table class="table card-table table-vcenter text-nowrap mb-0" dusk="top-products-table">
                    <thead><tr><th>{{ __('ui.product') }}</th><th class="c text-center">{{ __('ui.qty') }}</th><th class="text-end">{{ __('ui.table_amount') }}</th></tr></thead>
                    <tbody>
                    @forelse ($topProducts as $row)
                        <tr><td>{{ $row['product'] }}</td><td class="text-center">{{ $row['qty'] }}</td>
                            <td class="text-end">Rp {{ number_format($row['total'], 0, ',', '.') }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">-</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </x-ui.card>
        </div>
        <div class="col-lg-6">
            <x-ui.card :title="__('ui.lowest_margin')" :padded="false">
                <table class="table card-table table-vcenter text-nowrap mb-0" dusk="low-margin-table">
                    <thead><tr><th>{{ __('ui.product') }}</th><th class="text-center">{{ __('ui.margin') }}</th></tr></thead>
                    <tbody>
                    @forelse ($lowMargin as $row)
                        <tr>
                            <td>{{ $row['product'] }}</td>
                            <td class="text-center">
                                @if ($row['margin_pct'] !== null)
                                    <span class="{{ $row['margin_pct'] < 20 ? 'text-danger fw-semibold' : '' }}">{{ $row['margin_pct'] }}%</span>
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted py-3">{{ __('ui.no_cost_data') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </x-ui.card>
        </div>
    </div>

    {{-- ------------------------------------------------------------- Alerts --}}
    <div class="row row-deck row-cards mb-3">
        <div class="col-lg-5">
            <x-ui.card :title="__('ui.alerts')">
                @foreach ($alerts as $alert)
                    <x-dashboard.alert-item
                        level="{{ $alert['level'] }}"
                        title="{{ $alert['title'] }}"
                        :count="$alert['items']->count()"
                        duskId="alert-{{ $alert['key'] }}">
                        @forelse ($alert['items'] as $item)
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-1 small">
                                <span>
                                    {{ $alert['key'] === 'nostaff'
                                        ? ($item->product?->name ?? '-').' · '.$item->date_start->translatedFormat('d M H:i')
                                        : $item->name }}
                                    @isset($item->stock) <span class="text-danger">(sisa {{ $item->stock }})</span>@endisset
                                </span>
                                <a href="{{ $alert['key'] === 'nostaff'
                                            ? route('schedules.index')
                                            : route('inventory.create', ['product_id' => $item->id]) }}"
                                   class="btn btn-outline-primary btn-sm py-0">
                                    {{ $alert['key'] === 'nostaff' ? __('ui.assign_staff') : __('ui.stock_in_action') }}
                                </a>
                            </div>
                        @empty
                            <span class="text-muted small">{{ __('ui.no_items') }}</span>
                        @endforelse
                    </x-dashboard.alert-item>
                @endforeach
            </x-ui.card>
        </div>

        {{-- ------------------------------------------------ Transaksi terbaru --}}
        <div class="col-lg-7">
            <x-ui.card :title="__('ui.latest_transactions')" :padded="false" x-data="{ q: '' }">
                <div class="card-header py-2">
                    <input type="search" placeholder="{{ __('ui.search') }}…"
                           class="form-control form-control-sm w-auto"
                           x-model="q" dusk="recent-search">
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap" dusk="recent-transactions-table">
                        <thead><tr><th>#</th><th>{{ __('ui.customer') }}</th><th>{{ __('ui.date') }}</th><th>{{ __('ui.table_status') }}</th><th class="text-end">{{ __('ui.total') }}</th></tr></thead>
                        <tbody>
                        @forelse ($recentTransactions as $t)
                            <tr x-show="!q || (String($t.customer?->name ?? '') + String($t.id)).toLowerCase().includes(q.toLowerCase())"
                                dusk="recent-row-{{ $t->id }}">
                                <td class="fw-semibold">#{{ $t->id }}</td>
                                <td>{{ $t->customer?->name ?? '-' }}</td>
                                <td class="text-muted small">{{ $t->transaction_date?->translatedFormat('d M H:i') }}</td>
                                <td>
                                    <x-ui.badge color="{{ ['paid'=>'success','partial'=>'warning','confirmed'=>'info','draft'=>'secondary','void'=>'danger'][$t->status] ?? 'secondary' }}">
                                        {{ __('ui.status_'.$t->status) }}
                                    </x-ui.badge>
                                </td>
                                <td class="text-end">Rp {{ number_format($t->grand_total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">-</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="{{ route('transactions.index', array_merge(['status' => ''], ['branch_id' => $branch->id])) }}"
                       class="small" dusk="see-all-transactions">{{ __('ui.see_all_transactions') }} →</a>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
