@php
    $body = \App\Models\Setting::get('email_invoice_body');
    $footer = \App\Models\Setting::get('email_invoice_footer');
    $company = \App\Models\Setting::get('business_name') ?: config('app.name');

    if ($body) {
        $body = str_replace(
            [':name', ':no', ':total', ':paid', ':date'],
            [
                $invoice['customer_name'] ?? '-',
                $invoice['transaction_no'] ?? '-',
                number_format((float) ($invoice['grand_total'] ?? 0), 0, ',', '.'),
                number_format((float) ($invoice['paid_total'] ?? 0), 0, ',', '.'),
                $invoice['date'] ?? '-',
            ],
            $body
        );
    }
@endphp

@component('mail::message')
@if($body)
{!! nl2br(e($body)) !!}
@else
# {{ __('ui.email_invoice_title') }}

{{ __('ui.email_hello', ['name' => $invoice['customer_name'] ?? '-']) }}

{{ __('ui.email_invoice_intro') }}

@component('mail::table')
| {{ __('ui.transaction_no') }} | {{ $invoice['transaction_no'] ?? '-' }} |
| --- | --- |
| {{ __('ui.date') }} | {{ $invoice['date'] ?? '-' }} |
| {{ __('ui.total') }} | Rp {{ number_format((float) ($invoice['grand_total'] ?? 0), 0, ',', '.') }} |
| {{ __('ui.paid_total') }} | Rp {{ number_format((float) ($invoice['paid_total'] ?? 0), 0, ',', '.') }} |
@endcomponent
@endif

{{ $footer ?: __('ui.email_invoice_footer') }}

{{ $company }}
@endcomponent
