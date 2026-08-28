<x-layouts.app>
    <x-slot:title>{{ __('ui.reports') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.report_module') }}</x-slot:pretitle>

    <x-slot:page_actions>
        <a href="{{ route('reports.export', request()->only(['branch_id', 'date_from', 'date_until'])) }}"
           class="btn btn-outline-secondary" dusk="export-csv">
            CSV
        </a>
        <a href="{{ route('reports.excel', request()->only(['branch_id', 'date_from', 'date_until'])) }}"
           class="btn btn-success" dusk="export-excel">
            <i class="ti ti-file-spreadsheet me-1"></i>Excel
        </a>
        <a href="{{ route('reports.pdf', request()->only(['branch_id', 'date_from', 'date_until'])) }}"
           class="btn btn-primary" dusk="export-pdf" target="_blank">
            PDF
        </a>
    </x-slot:page_actions>

    <x-ui.card dusk="report-filter-card" :padded="false" class="mb-3">
        <div class="card-header"><form method="GET" class="row g-2 w-100">
            @if (! auth()->user()->hasRole('admin-cabang'))
                <div class="col-md-3">
                    <select name="branch_id" class="form-select">
                        <option value="">{{ __('ui.all_branches') }}</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" @selected(request('branch_id')==$b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-2">
                <input type="date" name="date_from" value="{{ request('date_from', now()->startOfMonth()->format('Y-m-d')) }}" class="form-control">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_until" value="{{ request('date_until', now()->endOfMonth()->format('Y-m-d')) }}" class="form-control">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary" dusk="apply-report-filter">{{ __('ui.filter') }}</button>
            </div>
        </form></div>
    </x-ui.card>

    {{-- Ringkasan laba-rugi --}}
    <div class="row row-deck row-cards mb-3">
        <div class="col-sm-4">
            <div class="card">
                <div class="card-body py-3">
                    <div class="subheader">{{ __('ui.revenue') }}</div>
                    <div class="h2 mb-0 fw-bold text-success" dusk="report-revenue">Rp {{ number_format($service->revenue(), 0, ',', '.') }}</div>
                    <div class="text-secondary small">{{ __('ui.paid_transactions') }}: {{ $service->transactionCount() }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card">
                <div class="card-body py-3">
                    <div class="subheader">{{ __('ui.expenses') }}</div>
                    <div class="h2 mb-0 fw-bold text-danger" dusk="report-expense">Rp {{ number_format($service->expenseTotal(), 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card">
                <div class="card-body py-3">
                    <div class="subheader">{{ __('ui.profit_estimate') }}</div>
                    <div class="h2 mb-0 fw-bold {{ $service->profitAndLoss()['profit'] >= 0 ? 'text-success' : 'text-danger' }}" dusk="report-profit">
                        Rp {{ number_format($service->profitAndLoss()['profit'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Perbandingan antar cabang --}}
    @if (! request('branch_id') && ! auth()->user()->hasRole('admin-cabang'))
        <x-ui.card :title="__('ui.per_branch_comparison')" :padded="false" class="mb-3">
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap" dusk="per-branch-table">
                    <thead>
                    <tr>
                        <th>{{ __('ui.table_branch') }}</th>
                        <th class="text-center">{{ __('ui.transactions') }}</th>
                        <th class="text-end">{{ __('ui.revenue') }}</th>
                        <th class="text-end">{{ __('ui.expenses') }}</th>
                        <th class="text-end">{{ __('ui.profit_estimate') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($perBranch as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['branch']->name }}</td>
                            <td class="text-center">{{ $row['transactions'] }}</td>
                            <td class="text-end">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($row['expense'], 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold {{ $row['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                Rp {{ number_format($row['profit'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('ui.empty_report_data') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    <div class="row row-deck row-cards mb-3">
        {{-- Penjualan per kategori --}}
        <div class="col-lg-6">
            <x-ui.card :title="__('ui.sales_per_category')" :padded="false">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap" dusk="category-sales-table">
                        <thead>
                        <tr><th>{{ __('ui.category') }}</th><th class="text-center">{{ __('ui.qty') }}</th><th class="text-end">{{ __('ui.table_amount') }}</th></tr>
                        </thead>
                        <tbody>
                        @forelse ($salesPerCategory as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['category'] }}</td>
                                <td class="text-center">{{ $row['qty'] }}</td>
                                <td class="text-end">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">{{ __('ui.empty_report_data') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>

        {{-- Kampanye marketing --}}
        <div class="col-lg-6">
            <x-ui.card :title="__('ui.campaign_roi')" :padded="false">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap" dusk="campaign-report-table">
                        <thead>
                        <tr>
                            <th>{{ __('ui.table_name') }}</th>
                            <th class="text-end">{{ __('ui.budget') }}</th>
                            <th class="text-end">{{ __('ui.table_spent') }}</th>
                            <th>{{ __('ui.utilization') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($campaigns as $row)
                            @php
                                $pct = $row['campaign']->budget > 0 ? min(100, round($row['spent'] / $row['campaign']->budget * 100)) : 100;
                                $over = $row['campaign']->budget > 0 && $row['spent'] > $row['campaign']->budget;
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $row['campaign']->name }}</td>
                                <td class="text-end">Rp {{ number_format($row['campaign']->budget, 0, ',', '.') }}</td>
                                <td class="text-end {{ $over ? 'text-danger fw-semibold' : '' }}">Rp {{ number_format($row['spent'], 0, ',', '.') }}</td>
                                <td style="min-width:90px">
                                    <div class="progress progress-sm">
                                        <div class="progress-bar {{ $over ? 'bg-danger' : 'bg-success' }}" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="small text-secondary">{{ $pct }}%</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">{{ __('ui.empty_campaigns') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
    </div>

    <div class="row row-deck row-cards">
        {{-- Top produk --}}
        <div class="col-lg-6">
            <x-ui.card :title="__('ui.top_products')" :padded="false">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap" dusk="top-products-table">
                        <thead>
                        <tr><th>#</th><th>{{ __('ui.product') }}</th><th class="text-center">{{ __('ui.qty') }}</th><th class="text-end">{{ __('ui.table_amount') }}</th></tr>
                        </thead>
                        <tbody>
                        @forelse ($topProducts as $i => $row)
                            <tr>
                                <td class="text-secondary">{{ $i + 1 }}</td>
                                <td class="fw-semibold">{{ $row['product'] }}</td>
                                <td class="text-center">{{ $row['qty'] }}</td>
                                <td class="text-end">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">{{ __('ui.empty_report_data') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>

        {{-- Top pelanggan --}}
        <div class="col-lg-6">
            <x-ui.card :title="__('ui.top_customers')" :padded="false">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap" dusk="top-customers-table">
                        <thead>
                        <tr><th>#</th><th>{{ __('ui.customer') }}</th><th class="text-center">{{ __('ui.orders') }}</th><th class="text-end">{{ __('ui.table_amount') }}</th></tr>
                        </thead>
                        <tbody>
                        @forelse ($topCustomers as $i => $row)
                            <tr>
                                <td class="text-secondary">{{ $i + 1 }}</td>
                                <td class="fw-semibold">{{ $row['customer'] }}</td>
                                <td class="text-center">{{ $row['orders'] }}</td>
                                <td class="text-end">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">{{ __('ui.empty_report_data') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
