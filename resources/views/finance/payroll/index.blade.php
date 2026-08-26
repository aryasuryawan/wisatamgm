<x-layouts.app>
    <x-slot:title>{{ __('ui.payroll_periods') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.payroll_module') }}</x-slot:pretitle>

    <x-slot:page_actions>
        @can('payroll.create')
            <a href="{{ route('payroll.create') }}" class="btn btn-primary" dusk="create-period">
                + {{ __('ui.add_period') }}
            </a>
        @endcan
    </x-slot:page_actions>

    <x-ui.card dusk="payroll-card" :padded="false">
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap table-hover" dusk="payroll-table">
                <thead>
                <tr>
                    <th>{{ __('ui.table_branch') }}</th>
                    <th>{{ __('ui.table_period') }}</th>
                    <th>{{ __('ui.table_status') }}</th>
                    <th class="text-end">{{ __('ui.net_total') }}</th>
                    <th class="text-end">{{ __('ui.table_action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($periods as $period)
                    @php
                        $statusColors = ['draft' => 'warning', 'approved' => 'info', 'closed' => 'success'];
                        $statusLabels = ['draft' => __('ui.status_draft'), 'approved' => __('ui.status_approved'), 'closed' => __('ui.status_closed')];
                    @endphp
                    <tr dusk="period-row-{{ $period->id }}">
                        <td>{{ $period->branch->name }}</td>
                        <td class="fw-semibold">
                            {{ $period->period_start->format('d M Y') }} – {{ $period->period_end->format('d M Y') }}
                        </td>
                        <td>
                            <x-ui.badge color="{{ $statusColors[$period->status] ?? 'secondary' }}" dusk="status-{{ $period->status }}">
                                {{ $statusLabels[$period->status] ?? $period->status }}
                            </x-ui.badge>
                        </td>
                        <td class="text-end fw-semibold">Rp {{ number_format($period->totalNet(), 0, ',', '.') }}</td>
                        <td class="text-end">
                            <a href="{{ route('payroll.show', $period) }}" class="btn btn-outline-primary btn-sm"
                               dusk="view-period-{{ $period->id }}">{{ __('ui.continue') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('ui.empty_periods') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $periods->links() }}</div>
    </x-ui.card>
</x-layouts.app>
