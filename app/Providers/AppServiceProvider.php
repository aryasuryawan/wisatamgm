<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

/**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.tabler');
        Paginator::defaultSimpleView('vendor.pagination.simple-tabler');

        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        // Override Fonnte config from DB settings
        $fonnte = \App\Models\Setting::allGrouped()['integrations'] ?? [];
        if (!empty($fonnte['fonnte_token'])) {
            config(['services.fonnte.url' => $fonnte['fonnte_url'] ?? config('services.fonnte.url')]);
            config(['services.fonnte.token' => $fonnte['fonnte_token']]);
        }

        // Override SMTP config from DB settings
        $smtp = \App\Models\Setting::allGrouped()['integrations'] ?? [];
        if (!empty($smtp['mail_host'])) {
            config(['mail.mailers.smtp.host' => $smtp['mail_host'] ?? config('mail.mailers.smtp.host')]);
            config(['mail.mailers.smtp.port' => $smtp['mail_port'] ?? config('mail.mailers.smtp.port')]);
            config(['mail.mailers.smtp.username' => $smtp['mail_username'] ?? config('mail.mailers.smtp.username')]);
            config(['mail.mailers.smtp.password' => $smtp['mail_password'] ?? config('mail.mailers.smtp.password')]);
            config(['mail.mailers.smtp.encryption' => $smtp['mail_encryption'] ?? config('mail.mailers.smtp.encryption')]);
        }

        // Override From config from DB settings
        $from = \App\Models\Setting::allGrouped()['integrations'] ?? [];
        if (!empty($from['mail_from_address'])) {
            config(['mail.from.address' => $from['mail_from_address'] ?? config('mail.from.address')]);
            config(['mail.from.name' => $from['mail_from_name'] ?? config('mail.from.name')]);
        }
    }
}
