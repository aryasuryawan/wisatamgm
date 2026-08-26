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
    <x-slot:title>{{ __('ui.receipt_title') }} #{{ $transaction->id }}</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h1 class="h4 fw-semibold mb-0" dusk="receipt-title">{{ __('ui.receipt_title') }} #{{ $transaction->id }}</h1>
            <x-ui.badge :color="$statusColors[$transaction->status]" dusk="tx-status">
                {{ __('ui.status_' . $transaction->status) }}
            </x-ui.badge>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('transactions.pdf', $transaction) }}" target="_blank"
               class="btn btn-primary btn-sm" dusk="download-pdf">PDF</a>
            @if ($remaining > 0 && $transaction->customer?->email)
                <form method="POST" action="{{ route('transactions.send-invoice', $transaction) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-info btn-sm" dusk="send-invoice-email">{{ __('ui.email_invoice') }}</button>
                </form>
            @endif
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()" dusk="print-receipt">
                {{ __('ui.print_receipt') }}
            </button>
            <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.cancel') }}</a>
        </div>
    </div>

    <x-ui.card dusk="receipt-card">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="text-muted small">{{ __('ui.date') }}</div>
                <div class="fw-semibold">{{ $transaction->transaction_date->translatedFormat('d M Y H:i') }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">{{ __('ui.branch') }}</div>
                <div class="fw-semibold">{{ $transaction->branch->name }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">{{ __('ui.customers') }}</div>
                <div class="fw-semibold">{{ $transaction->customer?->name ?? '-' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">{{ __('ui.cashier') }}</div>
                <div class="fw-semibold">{{ $transaction->cashier?->name ?? '-' }}</div>
            </div>
        </div>

        <table class="table table-sm align-middle" dusk="receipt-items">
            <thead>
            <tr>
                <th>{{ __('ui.product') }}</th>
                <th>{{ __('ui.qty') }}</th>
                <th class="text-end">{{ __('ui.price') }}</th>
                <th class="text-end">{{ __('ui.subtotal') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($transaction->items as $item)
                <tr dusk="receipt-item-{{ $item->id }}">
                    <td>{{ $item->product->name }}@if($item->schedule_id) <x-ui.badge color="info">{{ __('ui.assign_schedule') }} #{{ $item->schedule_id }}</x-ui.badge>@endif</td>
                    <td>{{ $item->qty }}</td>
                    <td class="text-end">Rp {{ number_format((float) $item->price, 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format((float) $item->lineTotal(), 0, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <td colspan="3" class="text-end">{{ __('ui.subtotal') }}</td>
                <td class="text-end">Rp {{ number_format((float) $transaction->subtotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" class="text-end">{{ __('ui.discount') }}</td>
                <td class="text-end">- Rp {{ number_format((float) $transaction->discount_total, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" class="text-end">{{ __('ui.tax') }}</td>
                <td class="text-end">Rp {{ number_format((float) $transaction->tax_total, 0, ',', '.') }}</td>
            </tr>
            <tr class="fw-bold">
                <td colspan="3" class="text-end">{{ __('ui.grand_total') }}</td>
                <td class="text-end" dusk="receipt-total">Rp {{ number_format((float) $transaction->grand_total, 0, ',', '.') }}</td>
            </tr>
            </tfoot>
        </table>

        <h2 class="h6 fw-semibold mt-3">{{ __('ui.paid_total') }}</h2>
        <table class="table table-sm" dusk="receipt-payments">
            <tbody>
            @forelse ($transaction->payments as $payment)
                <tr dusk="payment-row-{{ $payment->id }}">
                    <td>{{ __('ui.method_' . $payment->method) }}</td>
                    <td>{{ $payment->paid_at->translatedFormat('d M H:i') }}</td>
                    <td>
                        {{ $payment->reference_no }}
                        @if ($payment->proof_path)
                            <span class="ms-1"><x-ui.proof-link :path="$payment->proof_path" size="sm" dusk="payment-proof-{{ $payment->id }}" /></span>
                        @endif
                    </td>
                    <td class="text-end">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">-</td></tr>
            @endforelse
            </tbody>
        </table>

        <div class="d-flex justify-content-between align-items-center">
            <div class="fw-semibold">
                {{ __('ui.remaining') }}:
                <span class="text-danger" dusk="receipt-remaining">Rp {{ number_format((float) $remaining, 0, ',', '.') }}</span>
            </div>
        </div>
    </x-ui.card>

    <div class="row g-3 mt-0">
        @can('transactions.create')
            @if ($remaining > 0 && ! in_array($transaction->status, ['void']))
                <div class="col-lg-6">
                    <x-ui.card>
                        <h2 class="h6 fw-semibold mb-3">{{ __('ui.record_payment') }}</h2>
                        <form method="POST" action="{{ route('transactions.payments.store', $transaction) }}" enctype="multipart/form-data" dusk="payment-form">
                            @csrf
                            <div class="row g-2 align-items-end">
                                <div class="col-4">
                                    <x-ui.select name="method" :label="__('ui.payment_method')" required
                                                 :options="array_combine($paymentMethods, array_map(fn ($m) => __('ui.method_' . $m), $paymentMethods))" />
                                </div>
                                <div class="col-3">
                                    <x-ui.money name="amount" :label="__('ui.payment_amount')" required
                                                :value="(string) (int) $remaining" dusk="input-amount" />
                                </div>
                                <div class="col-3">
                                    <label for="proof" class="form-label fw-semibold">{{ __('ui.proof_attachment') }}</label>
                                    <input type="file" id="proof" name="proof" accept=".jpg,.jpeg,.png,.webp,.pdf"
                                           class="form-control form-control-sm" dusk="input-proof">
                                </div>
                                <div class="col-2">
                                    <x-ui.button type="submit" variant="primary" dusk="submit-payment">{{ __('ui.record_payment') }}</x-ui.button>
                                </div>
                            </div>
                            @error('proof') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </form>
                    </x-ui.card>
                </div>
            @endif
        @endcan

        @can('transactions.void')
            @if ($transaction->status !== 'void')
                <div class="col-lg-6">
                    <x-ui.card>
                        <h2 class="h6 fw-semibold mb-3 text-danger">{{ __('ui.void_transaction') }}</h2>
                        <form method="POST" action="{{ route('transactions.void', $transaction) }}"
                              onsubmit="return confirm('{{ __('ui.confirm_void') }}')" dusk="void-form">
                            @csrf
                            <x-ui.button type="submit" variant="outline-danger" dusk="void-transaction">{{ __('ui.void_transaction') }}</x-ui.button>
                        </form>
                    </x-ui.card>
                </div>
            @endif
        @endcan
    </div>
</x-layouts.app>
