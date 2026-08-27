@php $s = $settings['notifications'] ?? []; @endphp

<form method="POST" action="{{ route('settings.update', ['tab' => 'notifications']) }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="tab" value="notifications">

    <h3 class="card-title mb-3">WhatsApp Templates</h3>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <label class="form-label">{{ __('ui.wa_invoice_paid') }}</label>
            <textarea name="wa_invoice_paid" class="form-control font-monospace" rows="3" dusk="input-wa-invoice">{{ $s['wa_invoice_paid'] ?? 'Halo :name, pembayaran transaksi #:no sudah kami terima. Total: Rp :total. Terima kasih! - Tulamben Scuba' }}</textarea>
            <div class="form-hint">{{ __('ui.wa_invoice_paid_hint') }}</div>
        </div>
        <div class="col-12">
            <label class="form-label">{{ __('ui.wa_schedule_reminder') }}</label>
            <textarea name="wa_schedule_reminder" class="form-control font-monospace" rows="3" dusk="input-wa-reminder">{{ $s['wa_schedule_reminder'] ?? 'Halo :name! Pengingat :label untuk :product pada :date. Sampai jumpa! - Tulamben Scuba' }}</textarea>
            <div class="form-hint">{{ __('ui.wa_schedule_reminder_hint') }}</div>
        </div>
    </div>

    <h3 class="card-title mb-3">Email Templates</h3>

    <div class="row g-4">
        <div class="col-12">
            <x-ui.input name="email_invoice_subject" label="{{ __('ui.email_invoice_subject') }}"
                        :value="$s['email_invoice_subject'] ?? 'Invoice Transaksi #:no - Tulamben Scuba'" dusk="input-email-subject" />
        </div>
        <div class="col-12">
            <label class="form-label">{{ __('ui.email_invoice_body') }}</label>
            <textarea name="email_invoice_body" class="form-control" rows="6" dusk="input-email-body">{{ $s['email_invoice_body'] ?? "Halo :name,\n\nBerikut invoice untuk transaksi #:no.\nTotal: Rp :total\nSudah dibayar: Rp :paid\n\nTerima kasih!" }}</textarea>
            <div class="form-hint">Variabel: :name, :no, :total, :paid, :date</div>
        </div>
        <div class="col-12">
            <x-ui.input name="email_invoice_footer" label="{{ __('ui.email_invoice_footer') }}"
                        :value="$s['email_invoice_footer'] ?? ''" dusk="input-email-footer" />
        </div>
    </div>

    <div class="mt-4">
        <x-ui.button type="submit" variant="primary" dusk="save-notifications">{{ __('ui.save_changes') }}</x-ui.button>
    </div>
</form>
