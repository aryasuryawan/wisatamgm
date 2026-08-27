@php
// Status badge: semantic Tabler colors
$statusColors = [
    'draft' => 'secondary',
    'confirmed' => 'info',
    'paid' => 'success',
    'partial' => 'warning',
    'void' => 'danger',
];
@endphp

<x-layouts.app>
    <x-slot:title>{{ __('ui.receipt_title') }} #{{ $transaction->id }}</x-slot>

    {{-- Header aksi — tidak ikut print --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 d-print-none">
        <div class="d-flex align-items-center gap-2">
            <h1 class="h4 fw-semibold mb-0" dusk="receipt-title">{{ __('ui.receipt_title') }} #{{ $transaction->id }}</h1>
            <x-ui.badge :color="$statusColors[$transaction->status]" dusk="tx-status">
                {{ __('ui.status_' . $transaction->status) }}
            </x-ui.badge>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('transactions.pdf', $transaction) }}" target="_blank"
               class="btn btn-primary btn-sm" dusk="download-pdf">
                <i class="ti ti-file-text me-1"></i>PDF
            </a>
            @if ($remaining > 0 && $transaction->customer?->email)
                <form method="POST" action="{{ route('transactions.send-invoice', $transaction) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-info btn-sm" dusk="send-invoice-email">
                        <i class="ti ti-mail me-1"></i>{{ __('ui.email_invoice') }}
                    </button>
                </form>
            @endif
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()" dusk="print-receipt">
                <i class="ti ti-printer me-1"></i>{{ __('ui.print_receipt') }}
            </button>
            <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-arrow-left me-1"></i>{{ __('ui.cancel') }}
            </a>
        </div>
    </div>

    {{-- ═══ Area Kwitansi — frame dokumen resmi ═══ --}}
    <div class="sg-receipt-frame" dusk="receipt-card">

        {{-- Kop dokumen: hanya tampil di print --}}
        <div class="sg-receipt-header d-none d-print-block">
            <div class="sg-receipt-brand">
                <span class="sg-receipt-logo">SG</span>
                <div>
                    <div class="sg-receipt-brand-name">SIP Garden</div>
                    <div class="sg-receipt-brand-sub">{{ $transaction->branch->name }}</div>
                </div>
            </div>
            <div class="sg-receipt-title-print">{{ __('ui.receipt_document') }}</div>
        </div>

        {{-- Metadata transaksi --}}
        <div class="sg-receipt-meta" dusk="receipt-meta">
            <div class="sg-receipt-meta-item">
                <span class="sg-receipt-meta-label">{{ __('ui.date') }}</span>
                <span class="sg-receipt-meta-value">{{ $transaction->transaction_date->translatedFormat('d M Y H:i') }}</span>
            </div>
            <div class="sg-receipt-meta-item">
                <span class="sg-receipt-meta-label">{{ __('ui.branch') }}</span>
                <span class="sg-receipt-meta-value">{{ $transaction->branch->name }}</span>
            </div>
            <div class="sg-receipt-meta-item">
                <span class="sg-receipt-meta-label">{{ __('ui.customer') }}</span>
                <span class="sg-receipt-meta-value">{{ $transaction->customer?->name ?? '-' }}</span>
            </div>
            <div class="sg-receipt-meta-item">
                <span class="sg-receipt-meta-label">{{ __('ui.cashier') }}</span>
                <span class="sg-receipt-meta-value">{{ $transaction->cashier?->name ?? '-' }}</span>
            </div>
        </div>

        <hr class="my-3">

        {{-- Tabel item --}}
        <div class="table-responsive">
            <table class="table table-sm align-middle sg-receipt-table" dusk="receipt-items">
                <thead>
                <tr>
                    <th>{{ __('ui.product') }}</th>
                    <th class="text-center" style="width:60px">{{ __('ui.qty') }}</th>
                    <th class="text-end" style="width:130px">{{ __('ui.price') }}</th>
                    <th class="text-end" style="width:140px">{{ __('ui.subtotal') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($transaction->items as $item)
                    <tr dusk="receipt-item-{{ $item->id }}">
                        <td>
                            {{ $item->product->name }}
                            @if($item->schedule_id)
                                <x-ui.badge color="info">{{ __('ui.assign_schedule') }} #{{ $item->schedule_id }}</x-ui.badge>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->qty }}</td>
                        <td class="text-end sg-currency">Rp {{ number_format((float) $item->price, 0, ',', '.') }}</td>
                        <td class="text-end fw-semibold sg-currency">Rp {{ number_format((float) $item->lineTotal(), 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Ringkasan total --}}
        <div class="sg-receipt-summary">
            <table class="table table-sm mb-0">
                <tbody>
                <tr>
                    <td class="text-end text-muted" style="width:70%">{{ __('ui.subtotal') }}</td>
                    <td class="text-end sg-currency" style="width:30%">Rp {{ number_format((float) $transaction->subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="text-end text-muted">{{ __('ui.discount') }}</td>
                    <td class="text-end text-danger sg-currency">- Rp {{ number_format((float) $transaction->discount_total, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="text-end text-muted">{{ __('ui.tax') }}</td>
                    <td class="text-end sg-currency">Rp {{ number_format((float) $transaction->tax_total, 0, ',', '.') }}</td>
                </tr>
                <tr class="sg-receipt-total-row">
                    <td class="text-end fw-bold fs-5">{{ __('ui.grand_total') }}</td>
                    <td class="text-end fw-bold fs-5 sg-currency" dusk="receipt-total">Rp {{ number_format((float) $transaction->grand_total, 0, ',', '.') }}</td>
                </tr>
                </tbody>
            </table>
        </div>

        <hr class="my-3">

        {{-- Sisa Tagihan — banner prominen jika > 0 --}}
        @if ($remaining > 0)
            <div class="sg-remaining-banner" dusk="receipt-remaining">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold text-danger">{{ __('ui.remaining') }}</div>
                        <div class="h3 mb-0 fw-bold text-danger sg-currency">Rp {{ number_format((float) $remaining, 0, ',', '.') }}</div>
                    </div>
                    <i class="ti ti-alert-triangle text-danger" style="font-size:1.5rem;opacity:0.6"></i>
                </div>
            </div>
        @endif

        {{-- Tabel pembayaran --}}
        <h2 class="h6 fw-semibold mt-3 mb-2">{{ __('ui.paid_total') }}</h2>
        <div class="table-responsive">
            <table class="table table-sm sg-receipt-table" dusk="receipt-payments">
                <thead>
                <tr>
                    <th>{{ __('ui.payment_method') }}</th>
                    <th>{{ __('ui.date') }}</th>
                    <th>{{ __('ui.reference_no') }}</th>
                    <th class="text-end" style="width:140px">{{ __('ui.amount') }}</th>
                </tr>
                </thead>
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
                        <td class="text-end fw-semibold sg-currency">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">
                            <i class="ti ti-receipt me-1"></i>{{ __('ui.no_payments_recorded') }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($remaining <= 0)
            <div class="d-flex justify-content-between align-items-center">
                <div class="fw-semibold">
                    {{ __('ui.remaining') }}:
                    <span class="text-success fw-bold sg-currency" dusk="receipt-remaining">Rp 0</span>
                    <x-ui.badge color="success" class="ms-1">{{ __('ui.lunas') }}</x-ui.badge>
                </div>
            </div>
        @endif
    </div>

    {{-- ═══ Area Aksi — di luar frame kwitansi, tidak ikut print ═══ --}}
    <div class="row g-3 mt-0 d-print-none">
        @can('transactions.create')
            @if ($remaining > 0 && ! in_array($transaction->status, ['void']))
                <div class="col-lg-6 order-1">
                    <div class="card sg-action-card">
                        <div class="card-body">
                            <h2 class="h6 fw-semibold mb-3">
                                <i class="ti ti-cash me-1 text-primary"></i>{{ __('ui.record_payment') }}
                            </h2>
                            <form method="POST" action="{{ route('transactions.payments.store', $transaction) }}" enctype="multipart/form-data" dusk="payment-form">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-sm-6 col-lg-12">
                                        <x-ui.select name="method" :label="__('ui.payment_method')" required
                                                     :options="array_combine($paymentMethods, array_map(fn ($m) => __('ui.method_' . $m), $paymentMethods))" />
                                    </div>
                                    <div class="col-sm-6 col-lg-12">
                                        <x-ui.money name="amount" :label="__('ui.payment_amount')" required
                                                    :value="(string) (int) $remaining" dusk="input-amount" />
                                    </div>
                                    <div class="col-sm-6 col-lg-12">
                                        <label for="proof" class="form-label fw-semibold">{{ __('ui.proof_attachment') }}</label>
                                        <input type="file" id="proof" name="proof" accept=".jpg,.jpeg,.png,.webp,.pdf"
                                               class="form-control form-control-sm" dusk="input-proof">
                                        <div class="form-text">{{ __('ui.proof_hint') }}</div>
                                    </div>
                                    <div class="col-12">
                                        <x-ui.button type="submit" variant="primary" dusk="submit-payment" class="w-100">
                                            <i class="ti ti-check me-1"></i>{{ __('ui.record_payment') }}
                                        </x-ui.button>
                                    </div>
                                </div>
                                @error('proof') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endcan

        @can('transactions.void')
            @if ($transaction->status !== 'void')
                <div class="col-lg-6 order-2">
                    <div class="card sg-action-card sg-action-danger" x-data="{ confirming: false }">
                        <div class="card-body">
                            <h2 class="h6 fw-semibold mb-3 text-danger">
                                <i class="ti ti-alert-triangle me-1"></i>{{ __('ui.void_transaction') }}
                            </h2>
                            <p class="text-muted small mb-3">{{ __('ui.confirm_void') }}</p>
                            <form method="POST" action="{{ route('transactions.void', $transaction) }}" dusk="void-form">
                                @csrf
                                <template x-if="!confirming">
                                    <button type="button" class="btn btn-outline-danger w-100"
                                            @click="confirming = true" dusk="void-transaction">
                                        <i class="ti ti-x me-1"></i>{{ __('ui.void_transaction') }}
                                    </button>
                                </template>
                                <template x-if="confirming">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-danger flex-fill" dusk="void-confirm-yes">
                                            <i class="ti ti-check me-1"></i>{{ __('ui.void_transaction') }}
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary"
                                                @click="confirming = false" dusk="void-confirm-no">
                                            {{ __('ui.cancel') }}
                                        </button>
                                    </div>
                                </template>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endcan
    </div>
</x-layouts.app>
