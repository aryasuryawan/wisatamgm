<x-layouts.app>
    <x-slot:title>{{ __('ui.payroll_periods') }} — {{ $period->period_start->format('M Y') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.payroll_module') }} · {{ $period->branch->name }}</x-slot:pretitle>

    @php
        $statusColors = ['draft' => 'warning', 'approved' => 'info', 'closed' => 'success'];
        $statusLabels = ['draft' => __('ui.status_draft'), 'approved' => __('ui.status_approved'), 'closed' => __('ui.status_closed')];
        $isDraft = $period->status === 'draft';
        $canApprove = auth()->user()->can('payroll.approve');
    @endphp

    <x-slot:page_actions>
        @if ($isDraft)
            @can('payroll.edit')
                <button type="submit" form="generate-form" class="btn btn-outline-primary" dusk="generate-items">
                    {{ __('ui.generate_items') }}
                </button>
            @endcan
        @endif
        @if ($isDraft && $canApprove && $items->isNotEmpty())
            <form method="POST" action="{{ route('payroll.approve', $period) }}" class="d-inline"
                  onsubmit="return confirm('{{ __('ui.confirm_approve_period') }}')">
                @csrf
                <button type="submit" class="btn btn-primary" dusk="approve-period">{{ __('ui.approve') }}</button>
            </form>
        @endif
        @if ($period->status === 'approved' && $canApprove)
            <form method="POST" action="{{ route('payroll.close', $period) }}" class="d-inline"
                  onsubmit="return confirm('{{ __('ui.confirm_close_period') }}')">
                @csrf
                <button type="submit" class="btn btn-success" dusk="close-period">{{ __('ui.close_period') }}</button>
            </form>
        @endif
    </x-slot:page_actions>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">{{ __('ui.table_status') }}</div>
                    <x-ui.badge color="{{ $statusColors[$period->status] ?? 'secondary' }}" dusk="period-status">
                        {{ $statusLabels[$period->status] ?? $period->status }}
                    </x-ui.badge>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">{{ __('ui.table_period') }}</div>
                    <div class="fw-semibold" dusk="period-range">
                        {{ $period->period_start->format('d M Y') }} – {{ $period->period_end->format('d M Y') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">{{ __('ui.net_total') }}</div>
                    <div class="h2 mb-0 text-primary fw-bold" dusk="period-total">
                        Rp {{ number_format($period->totalNet(), 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($isDraft)
        <form id="generate-form" method="POST" action="{{ route('payroll.generate', $period) }}">@csrf</form>
    @endif

    <x-ui.card :title="__('ui.payroll_module')" :padded="false">
        <div class="table-responsive">
            <table class="table card-table table-vcenter" dusk="payroll-items-table">
                <thead>
                <tr>
                    <th>{{ __('ui.name') }}</th>
                    <th class="text-end">{{ __('ui.base_salary') }}</th>
                    <th class="text-center">{{ __('ui.trips_handled') }}</th>
                    <th class="text-center">{{ __('ui.pax_served') }}</th>
                    <th class="text-end">{{ __('ui.commission_total') }}</th>
                    <th class="text-end">{{ __('ui.deduction') }}</th>
                    <th class="text-end">{{ __('ui.net_total') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($items as $item)
                    <tr dusk="item-row-{{ $item->id }}">
                        <td class="fw-semibold">{{ $item->user?->name ?? '#' . $item->user_id }}</td>
                        <td class="text-end">Rp {{ number_format($item->base_salary, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $item->trips_count }}</td>
                        <td class="text-center">{{ $item->pax_count }}</td>
                        <td class="text-end">Rp {{ number_format($item->commission_total, 0, ',', '.') }}</td>
                        <td class="text-end">
                            @if ($isDraft)
                                <form method="POST" action="{{ route('payroll.deduction', [$period, $item]) }}"
                                      class="input-group input-group-sm justify-content-end" style="max-width: 170px; margin-left:auto"
                                      dusk="deduction-form-{{ $item->id }}">
                                    @csrf @method('PUT')
                                    <input type="number" name="deduction" value="{{ $item->deduction }}"
                                           min="0" step="1000" class="form-control text-end" dusk="input-deduction-{{ $item->id }}">
                                    <button type="submit" class="btn btn-outline-secondary" dusk="save-deduction-{{ $item->id }}">OK</button>
                                </form>
                            @else
                                Rp {{ number_format($item->deduction, 0, ',', '.') }}
                            @endif
                        </td>
                        <td class="text-end fw-semibold">Rp {{ number_format($item->net_total, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('ui.empty_periods') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($isDraft && $staffPool->isNotEmpty() && \App\Models\PayrollItem::count() === 0)
            <div class="card-footer text-secondary small">
                Staff pool: {{ $staffPool->pluck('name')->implode(', ') }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
