<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // Business
            'business' => [
                'business_name' => config('app.name'),
                'business_phone' => '',
                'business_email' => '',
                'business_address' => '',
                'business_city' => '',
                'business_npwp' => '',
                'business_website' => '',
                'business_footer_note' => '',
                'business_logo' => '',
            ],
            // Notifications
            'notifications' => [
                'wa_invoice_paid' => 'Halo :name, pembayaran transaksi #:no sudah kami terima. Total: Rp :total. Terima kasih! - Tulamben Scuba',
                'wa_schedule_reminder' => 'Halo :name! Pengingat :label untuk :product pada :date. Sampai jumpa! - Tulamben Scuba',
                'email_invoice_subject' => 'Invoice Transaksi #:no - Tulamben Scuba',
                'email_invoice_body' => "Halo :name,\n\nBerikut invoice untuk transaksi #:no.\nTotal: Rp :total\nSudah dibayar: Rp :paid\n\nTerima kasih!",
                'email_invoice_footer' => '',
            ],
            // PDF Templates
            'templates' => [
                'pdf_company_header' => '',
                'pdf_receipt_header' => '',
                'pdf_receipt_footer' => '',
                'pdf_paper_size' => 'a5',
                'pdf_show_logo' => '1',
                'pdf_show_tax' => '1',
            ],
        ];

        foreach ($defaults as $group => $items) {
            foreach ($items as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['group' => $group, 'value' => $value, 'type' => 'text']
                );
            }
        }
    }
}
