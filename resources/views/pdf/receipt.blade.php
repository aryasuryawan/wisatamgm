<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('ui.receipt_title') }} #{{ $transaction->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #1a1a1a; padding: 18px 22px; }
        .head { text-align: center; border-bottom: 2px solid #1a1a1a; padding-bottom: 10px; margin-bottom: 12px; }
        .brand { font-size: 17px; font-weight: bold; letter-spacing: 0.5px; }
        .meta { font-size: 10.5px; color: #555; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; }
        .info td { padding: 1.5px 0; vertical-align: top; }
        .info td:first-child { width: 34%; color: #555; }
        .items { margin-top: 12px; }
        .items th { border-bottom: 1px solid #999; text-align: left; padding: 4px 3px; font-size: 11px; text-transform: uppercase; color: #444; }
        .items th.r, .items td.r { text-align: right; }
        .items td { padding: 4px 3px; border-bottom: 1px dotted #ccc; vertical-align: top; }
        .totals { margin-top: 8px; margin-left: auto; width: 62%; }
        .totals td { padding: 2px 3px; }
        .totals td:first-child { color: #555; }
        .totals td.r { text-align: right; font-weight: normal; }
        .grand td { border-top: 1.5px solid #1a1a1a; font-weight: bold; font-size: 13.5px; padding-top: 5px; }
        .footer { margin-top: 22px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 8px; }
        .status { display: inline-block; border: 1px solid #1a1a1a; border-radius: 3px; padding: 2px 8px; font-weight: bold; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>

@php
    $brand = \App\Models\Setting::get('pdf_company_header')
        ?: ($transaction->branch?->name ?? config('app.name'));
    $footer = \App\Models\Setting::get('pdf_receipt_footer');
    $showTax = \App\Models\Setting::get('pdf_show_tax', '1') === '1';
@endphp

<div class="head">
    <div class="brand">{{ $brand }}</div>
    <div class="meta">
        {{ $transaction->branch?->address }}
        @if($transaction->branch?->phone) · {{ __('ui.phone') }} {{ $transaction->branch->phone }} @endif
    </div>
</div>

<table class="info">
    <tr><td>{{ __('ui.transaction_no') }}</td><td>: <strong>#{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}</strong></td></tr>
    <tr><td>{{ __('ui.date') }}</td><td>: {{ $transaction->transaction_date?->format('d/m/Y H:i') }}</td></tr>
    <tr><td>{{ __('ui.cashier') }}</td><td>: {{ $transaction->cashier?->name ?? '-' }}</td></tr>
    <tr><td>{{ __('ui.customer') }}</td><td>: {{ $transaction->customer?->name ?? '-' }}</td></tr>
</table>

<table class="items">
    <thead>
    <tr>
        <th style="width:46%">{{ __('ui.items') }}</th>
        <th class="r">{{ __('ui.qty') }}</th>
        <th class="r">{{ __('ui.price') }}</th>
        <th class="r">{{ __('ui.table_amount') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($transaction->items as $item)
        <tr>
            <td>{{ $item->product?->name }}</td>
            <td class="r">{{ $item->qty }}</td>
            <td class="r">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
            <td class="r">Rp {{ number_format($item->lineTotal(), 0, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><td>{{ __('ui.subtotal') ?? 'Subtotal' }}</td><td class="r">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</td></tr>
    @if ((float) $transaction->discount_total > 0)
        <tr><td>Diskon</td><td class="r">- Rp {{ number_format($transaction->discount_total, 0, ',', '.') }}</td></tr>
    @endif
    @if ($showTax && (float) $transaction->tax_total > 0)
        <tr><td>PPN 11%</td><td class="r">Rp {{ number_format($transaction->tax_total, 0, ',', '.') }}</td></tr>
    @endif
    <tr class="grand"><td>TOTAL</td><td class="r">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</td></tr>
    <tr><td>Sudah dibayar</td><td class="r">Rp {{ number_format($transaction->paidTotal(), 0, ',', '.') }}</td></tr>
    <tr><td>Sisa tagihan</td><td class="r">Rp {{ number_format((float) $remaining, 0, ',', '.') }}</td></tr>
</table>

<p style="margin-top:14px">
    <span class="status">{{ strtoupper($transaction->status) }}</span>
</p>

@foreach ($transaction->payments as $payment)
    <div style="font-size:11px; margin-top:6px;">
        Pembayaran {{ ucfirst($payment->method) }} — Rp {{ number_format($payment->amount, 0, ',', '.') }}
        @if ($payment->reference_no) ({{ $payment->reference_no }}) @endif
        · {{ $payment->paid_at?->format('d/m/Y H:i') }}
    </div>
@endforeach

<div class="footer">
    {{ $footer ?: __('ui.email_invoice_footer') }}<br>
    Dicetak {{ now()->format('d/m/Y H:i') }} — {{ \App\Models\Setting::get('business_name') ?: config('app.name') }}
</div>

</body>
</html>
