# Project: Sistem Informasi Manajemen Tulamben Scuba & ScubaGo

## Setiap Sesi Baru — WAJIB Lakukan Ini

1. Baca `docs/03-AI-AGENT-GUIDE.md` — ini system prompt utama. Ikuti semua aturan di dalamnya.
2. Baca `docs/04-PROGRESS-TRACKER.md` — tahu modul mana yang selesai, sedang dikerjakan, dan berikutnya.
3. Baca `docs/02-SYSTEM-DESIGN.md` — arsitektur, ERD, tech stack, reusable components.
4. Baca `docs/05-UI-STANDARD.md` — standard UI Tabler, WAJIB sebelum membuat/mengedit Blade/CSS apapun.
5. Baca `docs/01-PRD.md` — kebutuhan bisnis, ruang lingkup MVP, role & akses.

## Aturan Singkat

- Laravel + Alpine.js + Tabler UI (lihat `docs/05-UI-STANDARD.md`). Tidak pakai Livewire/Tailwind.
- Semua aset JS/CSS lokal via Vite build. Tidak CDN.
- Modular monolith: `app/Domain/*`
- Reusable Blade components `<x-ui.*>`
- Mobile/tablet-first UI
- Server-side validation wajib untuk input yang mengubah uang/stok
- Audit log untuk aksi sensitif
- Queue untuk job WhatsApp/email
- Jalankan `php artisan dusk --filter=NamaModul` sebelum tutup modul

## Internationalization (i18n)

- Default locale: `id` (Indonesian)
- Supported: `id`, `en`
- Language files: `resources/lang/id/` dan `resources/lang/en/`
- Semua string UI gunakan `__('ui.key')` atau `__('auth.key')`
- Language switcher: `<x-ui.language-switcher />` di navbar
- Middleware: `SetLocale` (auto-applied ke web group)
- Switch via URL: `?lang=en` atau `?lang=id`

### Adding New Strings

1. Tambah key di `resources/lang/id/ui.php`
2. Tambah key di `resources/lang/en/ui.php`
3. Gunakan `__('ui.key_name')` di Blade/Controller/Service
4. Jangan hardcode string UI!

## Workflow

1. Kerjakan 1 modul sampai tuntas (migration → model → policy → service → controller → view → test).
2. Update `docs/04-PROGRESS-TRACKER.md` setelah selesai modul.
3. Catat keputusan terbuka di progress tracker, jangan asumsikan sendiri untuk hal bisnis.
