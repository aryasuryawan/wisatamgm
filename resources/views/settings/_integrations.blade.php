@php $s = $settings['integrations'] ?? []; @endphp

<form method="POST" action="{{ route('settings.update', ['tab' => 'integrations']) }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="tab" value="integrations">

    <h3 class="card-title mb-3">
        <i class="ti ti-brand-whatsapp icon me-1"></i>
        WhatsApp — Fonnte API
    </h3>

    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <x-ui.input name="fonnte_url" label="Fonnte API URL"
                        :value="$s['fonnte_url'] ?? 'https://api.fonnte.com/send'"
                        placeholder="https://api.fonnte.com/send" dusk="input-fonnte-url" />
        </div>
        <div class="col-md-4">
            <x-ui.input name="fonnte_token" label="Fonnte Token"
                        :value="$s['fonnte_token'] ?? ''" type="password"
                        placeholder="Masukkan token Fonnte" dusk="input-fonnte-token" />
        </div>
    </div>

    <hr class="my-4">

    <h3 class="card-title mb-3">
        <i class="ti ti-mail icon me-1"></i>
        Email — SMTP
    </h3>

    <div class="row g-4">
        <div class="col-md-6">
            <x-ui.input name="mail_host" label="SMTP Host"
                        :value="$s['mail_host'] ?? 'smtp.gmail.com'" dusk="input-mail-host" />
        </div>
        <div class="col-md-2">
            <x-ui.input name="mail_port" label="Port"
                        :value="$s['mail_port'] ?? '587'" type="number" dusk="input-mail-port" />
        </div>
        <div class="col-md-4">
            <x-ui.select name="mail_encryption" label="Encryption"
                         :options="['tls' => 'TLS', 'ssl' => 'SSL', 'null' => 'None']"
                         :value="$s['mail_encryption'] ?? 'tls'" dusk="select-mail-encryption" />
        </div>
        <div class="col-md-6">
            <x-ui.input name="mail_username" label="Username"
                        :value="$s['mail_username'] ?? ''" dusk="input-mail-username" />
        </div>
        <div class="col-md-6">
            <x-ui.input name="mail_password" label="Password"
                        :value="$s['mail_password'] ?? ''" type="password" dusk="input-mail-password" />
        </div>
        <div class="col-md-6">
            <x-ui.input name="mail_from_address" label="Email Pengirim (From)"
                        :value="$s['mail_from_address'] ?? ''" type="email" dusk="input-mail-from" />
        </div>
        <div class="col-md-6">
            <x-ui.input name="mail_from_name" label="Nama Pengirim"
                        :value="$s['mail_from_name'] ?? ''" dusk="input-mail-from-name" />
        </div>
    </div>

    <div class="mt-4">
        <x-ui.button type="submit" variant="primary" dusk="save-integrations">{{ __('ui.save_changes') }}</x-ui.button>
    </div>
</form>
