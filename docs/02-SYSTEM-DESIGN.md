# System Design — Sistem Informasi Manajemen Tulamben Scuba & ScubaGo
Versi 1.0

## 1. Tech Stack

| Layer | Pilihan | Catatan |
|---|---|---|
| Backend framework | Laravel (versi LTS terbaru, mis. 11.x/12.x) | Full Laravel, **bukan Lumen** |
| Frontend interaktivitas | Alpine.js | **Tidak pakai Livewire** — semua state dinamis di sisi client via Alpine + fetch ke endpoint Laravel (JSON) atau form biasa |
| Templating | Blade (server-rendered) | Layout tablet-first |
| Styling | Bootstrap 5 (custom theme) *atau* CSS kustom (BEM) | **Tidak pakai Tailwind** — asumsi default Bootstrap 5, konfirmasi ke Owner |
| Database | MySQL 8 / PostgreSQL 15 | MySQL default (kompatibilitas hosting umum) |
| Queue | Redis + Laravel Queue (Horizon opsional) | Wajib untuk job WA/email (async, retry) |
| Auth | Laravel Breeze (Blade, tanpa Livewire) atau custom | RBAC via Spatie Laravel-Permission |
| PDF | Laravel Dompdf / Snappy | Invoice, laporan |
| Excel export | Laravel Excel (Maatwebsite) | Laporan |
| WhatsApp | Fonnte API (HTTP) | Kirim pesan via job queue |
| Email | Gmail API (OAuth2, Google API PHP Client) | Bukan SMTP polos — pakai Gmail Apps Sender resmi |
| Aset frontend (JS/CSS) | **Semua lokal** — di-bundle via Vite (`npm run build`) | **DILARANG memuat JS/CSS dari CDN** (jsdelivr/unpkg/cdn.jsdelivr, dsb.). Bootstrap & Alpine di-install via npm (`node_modules`), di-import lewat `resources/js/app.js`, hasil build di-serve dari `public/build`. Alasan: reliabilitas jaringan lokasi cabang + keamanan (tidak bergantung pihak ketiga). Font juga di-host lokal (bukan Google Fonts CDN) |
| Hosting | VPS/Cloud (Linux), Nginx + PHP-FPM | Multi-app: bisa 1 codebase, 2 domain (branding/tenant) |

## 2. Arsitektur Aplikasi

Pendekatan: **modular monolith** dalam satu codebase Laravel — cukup untuk skala 2 brand/cabang, menghindari kompleksitas microservices yang tidak perlu untuk MVP.

```
app/
  Domain/
    Branch/          (Cabang)
    Customer/         (Mini CRM)
    Catalog/           (Produk & kategori: Wisata/Jasa/Makanan/Alat/Transport/Merch)
    Rental/             (Penyewaan alat, unit, maintenance)
    Schedule/            (Jadwal trip/kelas, peserta, alokasi staf & alat)
    Transaction/          (POS, pembayaran, diskon)
    Finance/               (Biaya, laporan laba-rugi, marketing cost)
    Payroll/                (Gaji, komisi)
    Report/                  (Query/report builders)
    Notification/             (WhatsApp/Fonnte, Email/Gmail — job & log)
  Http/Controllers/...
  Models/...
  Policies/... (RBAC per modul)
Jobs/
  SendWhatsAppMessage.php
  SendGmailInvoice.php
resources/views/
  layouts/tablet.blade.php   (layout utama, touch-friendly)
  pos/, schedule/, rental/, finance/, report/, dashboard/
resources/js/
  alpine/  (komponen Alpine per fitur: pos-cart.js, schedule-calendar.js, dll)
```

Struktur `Domain/*` dipakai agar tiap modul PRD (4.1–4.13) punya boundary jelas: Model, Service, Policy, dan Controller-nya sendiri — memudahkan tracking progres per modul.

## 3. Model Data Inti (ERD Ringkas)

```
branches
  id, name, brand (tulambenscuba|scubago|lainnya), address, phone, is_active

users
  id, name, email, phone, password, is_active
user_branch (pivot)  -- user bisa akses banyak cabang
roles / permissions (spatie/laravel-permission)

customers
  id, branch_id (cabang asal), name, phone, email, source (organic/ads/referral),
  segment_tag, notes
customer_certifications
  id, customer_id, agency (PADI/SSI/dll), level, cert_number, cert_date

product_categories
  id, name (Wisata/Jasa/Makanan/SewaAlat/Transportasi/Merchandise), type_slug
products
  id, category_id, branch_id (nullable=global), name, base_price, unit, is_active, meta(json)

equipment_units
  id, product_id, code, condition, status (available/rented/maintenance), branch_id
equipment_rentals
  id, equipment_unit_id, transaction_id, rented_at, due_at, returned_at,
  condition_out, condition_in, penalty_amount
equipment_maintenance_logs
  id, equipment_unit_id, date, description, cost

stock_movements  -- kartu stok merchandise/inventori (bukan alat sewa per-unit)
  id, branch_id, product_id, type (in/out/adjustment/opname),
  qty, qty_after (stok hasil pergerakan, untuk audit cepat),
  ref_type (transaction|purchase|opname|manual|null), ref_id (nullable),
  unit_cost (nullable), notes, user_id, created_at

schedules
  id, branch_id, product_id (paket wisata/kelas), date_start, date_end,
  capacity, status
schedule_participants
  id, schedule_id, customer_id, transaction_item_id
schedule_staff
  id, schedule_id, user_id (guide/instruktur), role_in_trip

transactions
  id, branch_id, customer_id, user_id (kasir), transaction_date,
  status (draft/confirmed/paid/partial/void), subtotal, discount_total, grand_total
transaction_items
  id, transaction_id, product_id, qty, price, schedule_id (nullable),
  equipment_unit_id (nullable)
payments
  id, transaction_id, method, amount, paid_at, reference_no

discounts
  id, branch_id (nullable=global), code, type (nominal/percent), value,
  valid_from, valid_until, usage_limit, category_scope (json/nullable)
discount_usages
  id, discount_id, transaction_id, customer_id

expense_categories
  id, name (Operasional/Alat/Gaji/Marketing/SewaTempat/Lainnya)
expenses
  id, branch_id, category_id, description, amount, expense_date,
  campaign_ref (nullable, untuk marketing)
marketing_campaigns
  id, branch_id, name, channel, budget, start_date, end_date
  -- expenses.campaign_ref -> marketing_campaigns.id untuk ROI

payroll_periods
  id, branch_id, period_start, period_end, status
payroll_items
  id, payroll_period_id, user_id, base_salary, commission_total,
  deduction, net_total
  -- commission_total dihitung dari schedule_staff + rate komisi

whatsapp_logs
  id, customer_id, transaction_id (nullable), schedule_id (nullable),
  message, status (queued/sent/failed), provider_ref
email_logs
  id, customer_id, transaction_id (nullable), subject, status, provider_ref

audit_logs
  id, user_id, action, model_type, model_id, before(json), after(json), created_at
```

**Aturan multi-cabang**: semua tabel transaksional wajib punya `branch_id`. Query laporan selalu scoped via Policy/Global Scope berdasarkan cabang yang diizinkan untuk user login; Owner (role tertentu) bypass scope untuk melihat semua cabang sekaligus (mode konsolidasi).

## 4. Perhitungan Profitabilitas (Inti Kebutuhan Owner)

```
Laba Kotor per Cabang per Periode =
  SUM(transactions.grand_total WHERE status=paid, branch_id=X, tanggal in range)
  - SUM(expenses.amount WHERE branch_id=X, tanggal in range)   -- termasuk kategori Marketing & Gaji (payroll bisa dimasukkan sbg expense kategori "Gaji" otomatis dari payroll_items)
```
- Payroll period yang sudah "closed" otomatis generate baris di `expenses` (kategori Gaji) agar laporan laba-rugi konsisten satu sumber data.
- ROI Marketing = (Pendapatan transaksi yang pakai `discounts` terkait `campaign_ref` tertentu, atau `customers.source` = campaign) ÷ `marketing_campaigns.budget`.
- Dashboard Owner mengagregasi query di atas per cabang lalu ditampilkan sebagai perbandingan (bar chart sederhana, bisa pakai Chart.js via Alpine wrapper — tanpa Livewire).

## 5. Integrasi Eksternal

### 5.1 Fonnte (WhatsApp)
- Kirim via HTTP POST ke endpoint Fonnte dari `Job` (queue), bukan langsung di request — supaya transaksi tidak tertunda oleh API eksternal.
- Simpan `whatsapp_logs` dengan status; retry otomatis (Laravel queue `tries`/`backoff`) jika gagal.
- Trigger: transaksi confirmed, H-1/H-3 reminder jadwal (via scheduled command `schedule:remind`), invoice.

### 5.2 Gmail Apps Sender (Email)
- OAuth2 service account / user consent (Google API PHP Client), token refresh disimpan terenkripsi di tabel konfigurasi (bukan `.env` polos untuk token yang expire).
- Kirim invoice PDF sebagai lampiran, dan broadcast sederhana ke segmen (`customers.segment_tag`).
- Job async serupa WA, log ke `email_logs`.

## 6. Keamanan
- RBAC granular via Spatie Permission (bukan hardcode `if role == owner`).
- Audit log wajib untuk: void transaksi, edit harga manual, perubahan diskon, approval payroll.
- Rate limit endpoint POS untuk cegah double submit dari tablet (idempotency key per transaksi).
- Enkripsi kolom sensitif (token API, no. rekening jika ada) via Laravel encrypted casts.

## 7. Panduan UI Tablet
### 7.1 Prinsip Layout & Interaksi
- Layout landscape utama (viewport tablet 10"–12"), grid besar, tombol minimal 44×44px.
- POS: layout 2 kolom (kiri: katalog produk dengan kategori tab; kanan: keranjang & pembayaran) agar 1 layar tanpa banyak scroll.
- Form input mengutamakan pilihan (select/search-select) dibanding ketik manual (mis. pilih pelanggan via search, bukan isi ulang).
- Alpine.js dipakai untuk: filter katalog real-time, kalkulasi total keranjang, validasi form sebelum submit, modal konfirmasi (void/refund).
- Semua state dinamis di client tetap dikirim sebagai form POST/JSON standar ke controller Laravel — tidak ada logic bisnis penting yang hanya di client (validasi ganda di server wajib).

### 7.2 Reusable Components (Wajib — agar konsisten)
UI **wajib dibangun dari komponen reusable Blade**, bukan copy-paste markup antar halaman. Satu sumber kebenaran untuk tampilan & perilaku:

```
resources/views/components/
  layouts/        (app.blade.php, guest.blade.php — wrapper layout tablet)
  ui/
    button.blade.php      (varian primary/secondary/danger, size touch-friendly)
    input.blade.php       (label + error + atribut dusk otomatis)
    select.blade.php
    search-select.blade.php  (pilih pelanggan/produk via search + Alpine)
    card.blade.php / panel.blade.php
    table.blade.php       (wrapper tabel responsif)
    badge.blade.php       (status: paid/draft/maintenance, dll)
    modal.blade.php       (modal konfirmasi Alpine: void/refund/hapus)
    alert.blade.php       (flash success/error)
    pagination.blade.php
  forms/            (komponen gabungan: customer-picker, product-picker, date-range-filter)
```

Aturan:
- Setiap komponen punya props jelas dan default yang masuk akal; styling Bootstrap 5 dikustom HANYA di dalam komponen, tidak di view halaman.
- Komponen interaktif membungkus inisialisasi Alpine sendiri (`x-data` di dalam komponen), sehingga halaman tinggal memakai `<x-ui.modal>` tanpa setup manual.
- Setiap komponen otomatis menyertakan atribut `dusk="..."` (dari properti `$attributes`/nama) agar test Laravel Dusk stabil.
- Logika JS yang dipakai lintas komponen disimpan sebagai composable Alpine di `resources/js/alpine/` (mis. `useCart.js`, `useFilter.js`) — tidak diduplikasi.
- Jika butuh markup baru yang muncul ≥2 kali → jadikan komponen dulu sebelum dipakai di halaman kedua.

## 8. Deployment
- Environment: staging & production terpisah, `.env` per environment.
- CI sederhana: lint (Pint), test (PHPUnit/Pest) sebelum deploy.
- Backup DB harian otomatis (terutama tabel transaksi & keuangan).
- Monitoring queue (Horizon atau minimal `queue:work` via supervisor) khusus job WA/email agar tidak macet diam-diam.

## 9. Pengujian (Testing)
- **Unit/Feature test** (PHPUnit): setiap modul wajib punya minimal 1 feature test alur utama (validasi server-side, RBAC, perhitungan uang/stok).
- **Laravel Dusk (browser test)**: **wajib untuk semua fitur** — setiap modul harus punya Dusk test yang menguji alur utamanya lewat UI nyata di browser (headless Chrome), karena sistem tablet-first dan banyak state dinamis Alpine.js:
  - Login & RBAC (role melihat/melarang menu sesuai permission).
  - POS: pilih produk → keranjang → diskon → bayar → invoice.
  - Rental: checkout unit → return dengan denda.
  - Schedule: buat jadwal → daftarkan peserta → ubah status.
  - Inventory: stok masuk/keluar/opname → kartu stok ter-update.
  - Finance/Payroll/Report: filter cabang & tanggal, angka laba-rugi sesuai ekspektasi.
  - Integrasi WA/Email: trigger job (fake queue) → log tercatat.
- Dusk test berjalan di CI (staging) sebelum deploy produksi; gunakan `DatabaseMigrations` + factory data.
- Selector Dusk memakai atribut khusus `dusk="..."` pada elemen Blade — jangan andalkan class Bootstrap yang mudah berubah.
