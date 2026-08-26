@component('mail::message')
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

{{ __('ui.email_invoice_footer') }}

{{-- {{ config('app.name') }} --}}
@endcomponent
