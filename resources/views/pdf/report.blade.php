<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('ui.reports') }} {{ $service->dateFrom->format('d/m/Y') }}–{{ $service->dateUntil->format('d/m/Y') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1a1a1a; padding: 18px 22px; }
        h1 { font-size: 16px; } h2 { font-size: 12.5px; margin: 16px 0 6px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1.5px solid #1a1a1a; padding-bottom: 3px; }
        .head { text-align: center; border-bottom: 2px solid #1a1a1a; padding-bottom: 8px; margin-bottom: 10px; }
        .brand { font-size: 17px; font-weight: bold; }
        .meta { font-size: 10px; color: #555; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th { background: #f0f0f0; text-align: left; padding: 4px 5px; border-bottom: 1px solid #999; font-size: 10px; text-transform: uppercase; color: #444; }
        td { padding: 3.5px 5px; border-bottom: 1px dotted #ccc; }
        .r { text-align: right; } .c { text-align: center; }
        .cards { width: 100%; margin-bottom: 4px; }
        .cards td { width: 33.33%; border: none; }
        .cardbox { border: 1px solid #bbb; border-radius: 4px; padding: 7px 9px; margin-right: 8px; }
        .label { font-size: 9px; color: #666; text-transform: uppercase; }
        .value { font-size: 14px; font-weight: bold; margin-top: 2px; }
        .footer { margin-top: 20px; text-align: center; font-size: 9.5px; color: #777; border-top: 1px solid #ddd; padding-top: 6px; }
    </style>
</head>
<body>

<div class="head">
    <div class="brand">{{ config('app.name') }} — {{ __('ui.reports') }}</div>
    <div class="meta">{{ __('ui.table_period') }}: {{ $service->dateFrom->format('d M Y') }} – {{ $service->dateUntil->format('d M Y') }}</div>
</div>

<table class="cards"><tr>
    <td><div class="cardbox"><div class="label">{{ __('ui.revenue') }}</div><div class="value">Rp {{ number_format($service->revenue(), 0, ',', '.') }}</div></div></td>
    <td><div class="cardbox"><div class="label">{{ __('ui.expenses') }}</div><div class="value">Rp {{ number_format($service->expenseTotal(), 0, ',', '.') }}</div></div></td>
    <td><div class="cardbox"><div class="label">{{ __('ui.profit_estimate') }}</div><div class="value">Rp {{ number_format($service->profitAndLoss()['profit'], 0, ',', '.') }}</div></div></td>
</tr></table>

<h2>{{ __('ui.per_branch_comparison') }}</h2>
<table>
    <thead><tr><th>{{ __('ui.table_branch') }}</th><th class="c">{{ __('ui.orders') }}</th><th class="r">{{ __('ui.revenue') }}</th><th class="r">{{ __('ui.expenses') }}</th><th class="r">{{ __('ui.profit_estimate') }}</th></tr></thead>
    <tbody>
    @forelse ($perBranch as $row)
        <tr>
            <td>{{ $row['branch']->name }}</td>
            <td class="c">{{ $row['transactions'] }}</td>
            <td class="r">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</td>
            <td class="r">Rp {{ number_format($row['expense'], 0, ',', '.') }}</td>
            <td class="r">Rp {{ number_format($row['profit'], 0, ',', '.') }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="c">-</td></tr>
    @endforelse
    </tbody>
</table>

<h2>{{ __('ui.sales_per_category') }}</h2>
<table>
    <thead><tr><th>{{ __('ui.category') }}</th><th class="c">{{ __('ui.qty') }}</th><th class="r">{{ __('ui.table_amount') }}</th></tr></thead>
    <tbody>
    @foreach ($salesPerCategory as $row)
        <tr><td>{{ $row['category'] }}</td><td class="c">{{ $row['qty'] }}</td><td class="r">Rp {{ number_format($row['total'], 0, ',', '.') }}</td></tr>
    @endforeach
    </tbody>
</table>

<h2>{{ __('ui.top_products') }}</h2>
<table>
    <thead><tr><th>#</th><th>{{ __('ui.product') }}</th><th class="c">{{ __('ui.qty') }}</th><th class="r">{{ __('ui.table_amount') }}</th></tr></thead>
    <tbody>
    @foreach ($topProducts as $i => $row)
        <tr><td class="c">{{ $i + 1 }}</td><td>{{ $row['product'] }}</td><td class="c">{{ $row['qty'] }}</td><td class="r">Rp {{ number_format($row['total'], 0, ',', '.') }}</td></tr>
    @endforeach
    </tbody>
</table>

<h2>{{ __('ui.top_customers') }}</h2>
<table>
    <thead><tr><th>#</th><th>{{ __('ui.customer') }}</th><th class="c">{{ __('ui.orders') }}</th><th class="r">{{ __('ui.table_amount') }}</th></tr></thead>
    <tbody>
    @foreach ($topCustomers as $i => $row)
        <tr><td class="c">{{ $i + 1 }}</td><td>{{ $row['customer'] }}</td><td class="c">{{ $row['orders'] }}</td><td class="r">Rp {{ number_format($row['total'], 0, ',', '.') }}</td></tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    Dicetak {{ now()->format('d/m/Y H:i') }} — {{ config('app.name') }}
</div>

</body>
</html>
