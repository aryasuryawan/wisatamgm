<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->authorize('manage', Setting::class);
    }

    public function index(Request $request): View
    {
        $tab = $request->input('tab', 'business');

        $settings = Setting::allGrouped();

        return view('settings.index', [
            'tab' => $tab,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tab = $request->input('tab', 'business');

        $data = match ($tab) {
            'business' => $request->validate([
                'business_name' => ['required', 'string', 'max:255'],
                'business_phone' => ['nullable', 'string', 'max:32'],
                'business_email' => ['nullable', 'email', 'max:255'],
                'business_address' => ['nullable', 'string', 'max:500'],
                'business_city' => ['nullable', 'string', 'max:100'],
                'business_npwp' => ['nullable', 'string', 'max:32'],
                'business_website' => ['nullable', 'url', 'max:255'],
                'business_footer_note' => ['nullable', 'string', 'max:500'],
                'business_logo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
            ]),
            'notifications' => $request->validate([
                'wa_invoice_paid' => ['required', 'string', 'max:1000'],
                'wa_schedule_reminder' => ['required', 'string', 'max:1000'],
                'email_invoice_subject' => ['required', 'string', 'max:255'],
                'email_invoice_body' => ['required', 'string', 'max:5000'],
                'email_invoice_footer' => ['nullable', 'string', 'max:500'],
            ]),
            'templates' => $request->validate([
                'pdf_receipt_header' => ['nullable', 'string', 'max:255'],
                'pdf_receipt_footer' => ['nullable', 'string', 'max:500'],
                'pdf_paper_size' => ['nullable', 'in:a4,a5'],
                'pdf_show_tax' => ['nullable', 'in:0,1'],
                'pdf_show_logo' => ['nullable', 'in:0,1'],
                'pdf_company_header' => ['nullable', 'string', 'max:255'],
            ]),
            'integrations' => $request->validate([
                'fonnte_url' => ['nullable', 'url', 'max:255'],
                'fonnte_token' => ['nullable', 'string', 'max:255'],
                'mail_host' => ['nullable', 'string', 'max:255'],
                'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
                'mail_username' => ['nullable', 'string', 'max:255'],
                'mail_password' => ['nullable', 'string', 'max:255'],
                'mail_encryption' => ['nullable', 'in:tls,ssl,null'],
                'mail_from_address' => ['nullable', 'email', 'max:255'],
                'mail_from_name' => ['nullable', 'string', 'max:255'],
            ]),
        };

        foreach ($data as $key => $value) {
            if ($key === 'business_logo' && $request->hasFile('business_logo')) {
                $file = $request->file('business_logo');
                $name = 'settings/logo.'.$file->getClientOriginalExtension();
                $stream = fopen($file->getPathname(), 'r');
                \Storage::disk('public')->put($name, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $value = $name;
            }

            Setting::set($key, $value, $tab);
        }

        return redirect()
            ->route('settings.index', ['tab' => $tab])
            ->with('success', __('ui.settings_updated'));
    }
}
