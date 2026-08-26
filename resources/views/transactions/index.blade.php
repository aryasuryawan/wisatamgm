@php
$statusColors = [
    'draft' => 'bg-secondary',
    'confirmed' => 'bg-primary',
    'paid' => 'bg-success',
    'partial' => 'bg-warning text-dark',
    'void' => 'bg-danger',
];
@endphp

<x-layouts.app>
    <x-slot:title>{{ __('ui.page_transactions') }}</x-slot>

    <x-slot:page_actions>
        @can('transactions.create')
            <a href="{{ route('transactions.create') }}" class="btn btn-primary" dusk="open-pos">
                {{ __('ui.pos') }}
            </a>
        @endcan
    </x-slot:page_actions>

    <x-ui.card dusk="transactions-card" :padded="false">
        <div class="card-header"><form method="GET" action="{{ route('transactions.index') }}" class="row g-2 w-100">
            <div class="col-md-3">
                <x-ui.select name="status" :label="__('ui.table_status')"
                             :options="['' => __('ui.all_status')] + array_combine($statuses, array_map(fn ($s) => __('ui.status_' . $s), $statuses))"
                             :value="request('status')" />
            </div>
            <div class="col-md-3">
                <x-ui.select name="branch_id" :label="__('ui.branch')"
                             :options="['' => __('ui.all_branches')] + $branches->pluck('name', 'id')->all()"
                             :value="request('branch_id')" />
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-secondary mb-3" dusk="filter-button">
                    {{ __('ui.filter') }}
                </button>
            </div>
        </form></div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap table-hover" dusk="transactions-table">
                <thead>
                <tr>
                    <th>{{ __('ui.transaction_no') }}</th>
                    <th>{{ __('ui.date') }}</th>
                    <th>{{ __('ui.customers') }}</th>
                    <th>{{ __('ui.cashier') }}</th>
                    <th class="text-end">{{ __('ui.grand_total') }}</th>
                    <th>{{ __('ui.table_status') }}</th>
                    <th class="text-end">{{ __('ui.action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($transactions as $tx)
                    <tr dusk="transaction-row-{{ $tx->id }}">
                        <td class="fw-semibold">#{{ $tx->id }}</td>
                        <td>{{ $tx->transaction_date->translatedFormat('d M Y H:i') }}</td>
                        <td>{{ $tx->customer?->name ?? '-' }}</td>
                        <td>{{ $tx->cashier?->name ?? '-' }}</td>
                        <td class="text-end">Rp {{ number_format((float) $tx->grand_total, 0, ',', '.') }}</td>
                        <td>
                            <x-ui.badge :color="$statusColors[$tx->status]" dusk="tx-status-{{ $tx->id }}">
                                {{ __('ui.status_' . $tx->status) }}
                            </x-ui.badge>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('transactions.show', $tx) }}"
                               class="btn btn-outline-secondary btn-sm" dusk="view-transaction-{{ $tx->id }}">
                                {{ __('ui.detail') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">{{ __('ui.empty_transactions') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">{{ $transactions->links() }}</div>
    </x-ui.card>
</x-layouts.app>
