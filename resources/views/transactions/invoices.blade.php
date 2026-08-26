<x-layouts.app>
    <x-slot:title>{{ __('ui.invoices') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.nav_sales') }}</x-slot:pretitle>

    <x-ui.card dusk="invoices-card" :padded="false">
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap table-hover" dusk="invoices-table">
                <thead>
                <tr>
                    <th>{{ __('ui.transaction_no') }}</th>
                    <th>{{ __('ui.customer') }}</th>
                    <th>{{ __('ui.table_branch') }}</th>
                    <th>{{ __('ui.date') }}</th>
                    <th class="text-end">{{ __('ui.total') }}</th>
                    <th class="text-end">{{ __('ui.remaining') }}</th>
                    <th class="text-end">{{ __('ui.table_action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($invoices as $invoice)
                    @php
                        $remaining = max(0, (float) $invoice->grand_total - $invoice->paidTotal());
                    @endphp
                    <tr dusk="invoice-row-{{ $invoice->id }}">
                        <td class="fw-semibold">#{{ str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $invoice->customer?->name ?? '-' }}</td>
                        <td>{{ $invoice->branch?->name }}</td>
                        <td class="text-muted">{{ $invoice->transaction_date?->format('d M Y') }}</td>
                        <td class="text-end">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                        <td class="text-end fw-semibold text-danger">Rp {{ number_format($remaining, 0, ',', '.') }}</td>
                        <td class="text-end">
                            <a href="{{ route('transactions.show', $invoice) }}" class="btn btn-outline-primary btn-sm"
                               dusk="open-invoice-{{ $invoice->id }}">{{ __('ui.continue') }}</a>
                            <a href="{{ route('transactions.pdf', $invoice) }}" target="_blank"
                               class="btn btn-outline-secondary btn-sm" dusk="invoice-pdf-{{ $invoice->id }}">PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('ui.empty_invoices') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <span class="fw-semibold">
                {{ __('ui.total_outstanding') }}:
                <span class="text-danger">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</span>
            </span>
            {{ $invoices->links() }}
        </div>
    </x-ui.card>
</x-layouts.app>
