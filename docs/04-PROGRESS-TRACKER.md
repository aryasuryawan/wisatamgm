# Progress Tracker — Sistem Manajemen Tulamben Scuba & ScubaGo
Update file ini setiap kali menyelesaikan atau memulai sebuah task (lihat `03-AI-AGENT-GUIDE.md` bagian 3).

## Status Ringkas
| Modul | Status | Terakhir Update |
|---|---|---|
| Branch & Auth/RBAC | Selesai | 2026-08-24 |
| Internationalization (i18n) | Selesai | 2026-08-24 |
| Customer (Mini CRM) | Selesai | 2026-08-24 |
| Catalog/Produk & Kategori | Selesai | 2026-08-24 |
| Equipment/Rental | Selesai | 2026-08-24 |
| Inventory/Stok | Selesai | 2026-08-24 |
| Schedule | Selesai | 2026-08-24 |
| Transaction/POS | Selesai | 2026-08-24 |
| Discount | Selesai | 2026-08-24 |
| Finance/Expense | Selesai | 2026-08-25 |
| Payroll | Selesai (Dusk ditunda) | 2026-08-25 |
| Report | Selesai (export CSV; PDF open) | 2026-08-25 |
| Dashboard Owner | Selesai (chart CSS bar, tanpa lib) | 2026-08-25 |
| WhatsApp (Fonnte) | Selesai — Dusk ditunda | 2026-08-25 |
| Email (Gmail Sender) | Selesai via mailer pluggable; Gmail API OAuth menunggu credentials | 2026-08-25 |
| Booking (kamar/meeting/camp) | Selesai — Dusk ditunda | 2026-08-25 |

Status yang dipakai: `Belum mulai` / `Sedang Dikerjakan` / `Selesai` / `Blocked`

## Sedang Dikerjakan
_(kosong)_

## Data Simulasi Juni–Juli 2026
- **`php artisan db:seed --class=SimulationSeeder`** (2026-08-25) — simulasi operasional provider diving Bali, 2 bulan penuh. Idempotent (re-run membersihkan & mengisi ulang data transaksional).
- Isi: 10 staf (gaji tetap + komisi per-pax/per-trip), 18 pelanggan (lokal/asing, source organic/ads/referral/walk_in), 21 produk, 56 jadwal completed (+2 upcoming), 218 transaksi (215 paid / 2 DP partial / 1 void), bensin boat ±11x, sewa Avanza+driver 2 bulan, galon/listrik/internet/ATK, servis alat, pajak mobil, 4 kampanye iklan dgn spend terhubung, payroll 4 periode CLOSED (auto jadi expense Gaji).
- Angka hasil: omzet Rp 198 jt, biaya Rp 51,9 jt (incl. gaji Rp 35,7 jt), laba Rp 146 jt.
- WA/email invoice masuk antrian (`jobs` table, QUEUE_CONNECTION=database) — jalankan `php artisan queue:work` untuk memproses; tanpa FONNTE_TOKEN status WA log = failed.


## Tertunda
- **Dusk test Payroll** — ditunda oleh owner (2026-08-25): "tunda test dengan dusk sampai semua fitur selesai". Test sudah ditulis (`tests/Browser/PayrollTest.php`, alur create→generate→approve→close) tapi belum hijau (server dev mati saat run terakhir, `ERR_CONNECTION_REFUSED`). Catatan: `database/dusk.sqlite` korup 2x selama sesi ini — kalau muncul "database disk image is malformed", hapus file + buat kosong lagi.
- Jalankan semua Dusk test yang tertunda sebelum modul dianggap benar-benar tuntas.

## Selesai
- **Branch & Auth/RBAC** (2026-08-24)
  - Migrations: branches, user_branch, audit_logs, add phone+is_active ke users
  - Models: Branch, User (HasRoles), AuditLog
  - Custom auth: LoginController (throttle 10/min, cek is_active)
  - RBAC: Spatie Permission — role Owner/Admin Cabang/Kasir/Guide/Finance/Marketing, permission branches.view/create/edit/delete
  - CRUD Branch: controller, service (audit trail), policy, views (tablet-first Bootstrap 5)
  - Reusable components: button, input, select, checkbox, card, alert, layouts/app+guest
  - Frontend: Bootstrap 5 + Alpine.js lokal via Vite (no CDN)
  - Seeders: RolesPermissionSeeder, BranchSeeder, AdminUserSeeder
  - Tests: 13 PHPUnit + 2 Dusk (LoginTest, BranchTest)
  - Bug fix: `pluck('id')` ambigu di pivot → ganti `pluck('users.id')`; Controller base tambah AuthorizesRequests/ValidatesRequests

- **Internationalization (i18n)** (2026-08-24)
  - Language files: `resources/lang/id/` dan `resources/lang/en/` (ui.php, auth.php, messages.php)
  - Middleware: `SetLocale` (auto-applied ke web group, switch via `?lang=id|en`)
  - Language switcher: `<x-ui.language-switcher />` di navbar
  - Refactored semua Blade views: gunakan `__()` helper untuk semua string UI
  - Refactored controllers: gunakan `__()` untuk flash messages & validation
  - Refactored services: gunakan `__()` untuk error messages
  - Default locale: `id` (Indonesian)

- **Customer (Mini CRM)** (2026-08-24)
  - Migration tambahan: `nationality_type` (indonesia/international), `preferences` (JSON)
  - Customer model: fillable, casts, scopes (search, branch, nationality), preferences getter/setter
  - CustomerController: CRUD + show (profile page), nationality filter
  - CustomerPolicy: branch-scoping untuk admin-cabang
  - Views: index (nationality badge, filter), create/edit (nationality + preferences), show (profile page)
  - Certifications: expiry_date added ke form
  - Feature tests: 15 tests (CRUD, nationality, preferences, validation, permissions)
  - Dusk tests: 8 tests (list, search, create, edit, profile, filter, delete)
  - I18n: semua string pakai `__()` helper

- **Catalog/Produk & Kategori** (2026-08-24)
  - Migrations: products, product_categories (complete)
  - Models: Product (with categories, equipment, stock relationships), ProductCategory
  - Controllers: ProductController (CRUD + search + filter), ProductCategoryController (CRUD)
  - Policies: ProductPolicy, ProductCategoryPolicy (permission-gated)
  - Views: Products (index, create, edit, _form), ProductCategories (index, create, edit, _form)
  - Sidebar: Products + Categories links added
  - Factories: ProductFactory, ProductCategoryFactory
  - Feature tests: ProductTest (9 tests), ProductCategoryTest (8 tests)
  - Dusk tests: ProductTest (7 tests), ProductCategoryTest (5 tests)
  - I18n: semua string pakai `__()` helper
  - Flash messages: product_created, product_updated, product_deleted, category_created, category_updated, category_deleted, category_has_products

- **Equipment/Rental** (2026-08-24)
  - Migrations: equipment_units, equipment_maintenance_logs
  - Models: EquipmentUnit (scopeAvailable), EquipmentMaintenanceLog (performer relation)
  - Controller: CRUD + addMaintenance, branch-scoping untuk admin-cabang (list & dropdown cabang)
  - Policy: EquipmentUnitPolicy (permission-gated)
  - Validasi: kode unit unik per cabang (`Rule::unique` + where branch_id)
  - Audit log: create/update/delete/maintenance → tabel `audit_logs` (before/after/ip)
  - Views: index (filter status+cabang), create, edit (_form + maintenance log inline)
  - I18n: label tipe maintenance diterjemahkan (maintenance_routine/repair/inspection/replacement)
  - Feature tests: 10 tests (CRUD, maintenance, validasi, unique code, permission)
  - Dusk tests: 6 tests (list, create, edit condition, tambah maintenance, filter status, validasi form kosong)
  - Fix penting: route model binding — param controller HARUS sama dengan placeholder resource (`{equipment}` → `$equipment`); sebelumnya pakai `$unit` sehingga binding gagal diam-diam (update malah insert record baru)

- **Inventory/Stok** (2026-08-24)
  - Sudah ada: StockMovement CRUD (stock in + opname)
  - Views: index (low stock alert), create (stock in/opname)
  - Controller, Service (stock in/out/adjust), Factory, Feature tests

- **Schedule** (2026-08-24)
  - Migrations: schedules (branch/product FK, date_start/end, capacity, status enum draft|confirmed|ongoing|completed|cancelled), schedule_participants (unique schedule+customer, transaction_item_id nullable tanpa FK — menunggu modul Transaction), schedule_staff (unique schedule+user, role_in_trip)
  - Models: Schedule (seatsLeft, scopeForBranches/Upcoming), ScheduleParticipant, ScheduleStaff (ROLES: guide/instructor/assistant/divemaster)
  - Service: `app/Domain/Schedule/Services/ScheduleService` — transisi status dikunci (draft→confirmed→ongoing→completed, cancel dari draft/confirmed), kapasitas dicek server-side, duplikasi peserta/staf diblok, audit log semua aksi
  - Policy: SchedulePolicy — branch-scoping admin-cabang; dive-guide hanya jadwal tempat dia ditugaskan
  - Controller: CRUD + changeStatus + participants (add/remove) + staff (add/remove)
  - Routes: route manual create/store/edit/update diletakkan SEBELUM resource (show `{schedule}` menangkap literal `/create` jika resource didahulukan)
  - Views: index (filter status+cabang), create/edit/_form, show (info + tombol transisi status + peserta + staf)
  - Sidebar: link Jadwal (dusk=nav-schedules)
  - I18n: lengkap id/en (status, aksi transisi, peserta, staf, flash messages)
  - Feature tests: 17 (CRUD, kapasitas penuh, duplikat, transisi valid/invalid, branch scope, dive-guide scope, permission)
  - Dusk tests: 3 (list, alur lengkap buat→peserta→status, validasi)
  - Catatan Dusk: input `datetime-local` TIDAK boleh `type()` (browser interpretasi aneh, mis. tahun 60826) — set value via `->script("document.querySelector(...).value = '...'")`

- **Perbaikan menyeluruh Dusk legacy + bug tersembunyi** (2026-08-24)
  - BUG: `customers/_form.blade.php` akses `$customer->getPreference()` di halaman create → 500 (variabel undefined; `??` tidak melindungi method call). Fix: `($customer ?? null)?->` di semua referensi. Ditambah smoke test render halaman create semua modul (`tests/Feature/SmokePagesTest.php`)
  - Rewrite suite Dusk lama (Branch/Customer/Product/ProductCategory) yang ditulis tanpa pernah dijalankan: selector `@dusk` konsisten, dialog confirm pakai `waitForDialog()->acceptDialog()`, assertion teks → `assertVisible('@...')`, alur delete Customer pakai role owner (admin-cabang tidak punya customers.delete)
  - Catatan: harga produk step=1000 → Dusk edit test harus isi harga kelipatan (HTML5 validation blok submit)

- **Transaction/POS** (2026-08-24)
  - **Keputusan owner**: PPN AKTIF 11% (server-side, config/transactions.php); metode bayar: cash/transfer/qris/card
  - Migrations: transactions (branch/customer/user FK, status draft|confirmed|paid|partial|void, subtotal/discount_total/tax_total/grand_total, idempotency_key unique), transaction_items (price server-side, schedule_id + equipment_unit_id nullable), payments (multi-row = split payment)
  - Models: Transaction (paidTotal, isFullyPaid), TransactionItem (lineTotal), Payment (METHODS const)
  - Service `Domain/Transaction/Services/TransactionService`: harga SELALU dari products.base_price (input client diabaikan), PPN dihitung server-side, stok keluar otomatis utk kategori makanan+merchandise (StockService::stockOut, ref transaction), link ScheduleParticipant + transaction_item_id, split payment → status partial/paid, counter customer (total_orders/total_spent) naik saat paid, void = restore stok + revert counter + audit WAJIB, idempotency key cegah double-submit tablet
  - Policy: view/create/pay (branch scope), void = transactions.void (owner only sesuai matrix)
  - Routes: payments.store + void sebelum resource; store exclude dari resource (hindari duplikat nama) + throttle 30/min
  - Views: POS 2-kolom (katalog+tabs | keranjang+PPN live+split payment) Alpine inline `posCart`, index (filter status/cabang), show = receipt (print, catat pembayaran, void)
  - Komponen baru: `x-ui.badge`; layout dapat `@stack('scripts')`
  - I18n lengkap id/en
  - Feature tests: 15 (PPN math, client price ignored, stok keluar/blocked, jadwal link, split→paid + counter, overpay block, idempotency, void restore, RBAC kasir/owner, branch scope, validasi)
  - Dusk tests: 3 (POS flow penuh → receipt, DP→lunas→void, list+filter)
   - **Gotcha Blade+Alpine** (dicatat!): `:disabled` pada komponen `<x-ui.*>` diparse sebagai prop PHP → gunakan `x-bind:disabled`; `@push` HARUS di dalam `<x-layouts.app>` (di luar = eksekusi setelah `@stack` dirender); input number `min=1 step=1000` memblokir 50.000 via HTML5 validation → pakai `min=0`

- **Finance/Expense** (2026-08-25)
  - Migrations: expense_categories, marketing_campaigns, expenses (+ ref_type/ref_id generik untuk baris otomatis dari payroll)
  - Models: ExpenseCategory (SLUGS const: operasional/alat/gaji/marketing/sewa-tempat/lainnya), Expense, MarketingCampaign
  - Service: `Domain/Finance/Services/ExpenseService` — audit trail create/update/delete; expense hasil generate sistem (`ref_type` != null) DIKUNCI dari edit/delete manual
  - Controllers: ExpenseController (filter branch/kategori/date-range + scope admin-cabang), MarketingCampaignController
  - Policies: ExpensePolicy, MarketingCampaignPolicy; routes `expenses` + `marketing-campaigns` (middleware permission OR pakai pipe)
  - Views: expenses index/create/edit/_form (x-ui.money untuk nominal), campaigns index/create/edit/_form (progress bar budget, badge over-budget)
  - Seeder: ExpenseCategorySeeder (masuk DatabaseSeeder)
  - Tests: Feature 14 tests lulus; Dusk FinanceTest 3 tests lulus

- **Payroll** (2026-08-25) — Dusk ditunda oleh owner
  - **Keputusan owner**: komisi per-user configurable (per_pax/per_trip/none), base_salary di tabel users, alur Draft → Approved → Closed, UI lanjut Tabler (ternyata @tabler/core sudah terpasang)
  - Migrations: users +base_salary/commission_type/commission_rate; payroll_periods (status draft|approved|closed, created_by/approved_by/approved_at/closed_at), payroll_items (unique period+user, snapshot trips/pax/base_salary)
  - Service: `Domain/Payroll/Services/PayrollService` — komisi dari schedule_staff pada jadwal COMPLETED dalam rentang periode (per_pax: rate×pax, per_trip: rate×trips); staf tetap cabang (pivot user_branch) ikut digaji walau tanpa trip; approve/close/updateDeduction semua ber-audit; close otomatis buat Expense kategori "gaji" (ref payroll_period) sesuai System Design §4
  - Policy: PayrollPeriodPolicy — update hanya draft; approve/close butuh `payroll.approve` (owner saja sesuai matrix seeder; finance hanya view/create)
  - Controller: PayrollPeriodController (index/create/store/show/generate/deduction/approve/close); validasi overlap periode server-side
  - Views: payroll index/create/show (summary card status/range/total, tabel item, edit deduction inline saat draft, tombol approve/close dgn confirm dialog)
  - Sidebar: link Biaya + Gaji baru (icon ti-wallet / ti-cash-banknote)
  - Gotcha baru: nullsafe `$var?->method()` TETAP error kalau $var undefined → pola aman `($var ?? null)?->...` (sama seperti kasus customer preferences)
  - Tests: Feature PayrollTest 10 tests lulus (komisi math per pax/per trip, staf tetap tanpa trip, regenerasi tidak duplikat, transisi invalid, RBAC finance vs admin-cabang, close→expense locked, deduction recalc, overlap)

- **Report** (2026-08-25) — export Excel/PDF belum (belum ada package; lihat open question)
  - Service: `Domain/Report/Services/ReportService` — omzet HANYA transaksi `paid` (System Design §4); biaya = semua `expenses` (termasuk payroll otomatis). Method: profitAndLoss, perBranch (perbandingan), salesPerCategory, topProducts(5), topCustomers(5), campaigns (budget vs spent + progress bar)
  - **Gotcha SQL**: join customers → kolom `branch_id` ambigu → WAJIB qualify `transactions.branch_id` di query laporan (sama seperti gotcha pluck users.id dulu)
  - Controller: ReportController (filter branch + date range; admin-cabang terkunci ke cabangnya; kasir/guide tanpa akses)
  - Route: GET `/reports` (permission reports.view); sidebar link Laporan (ti-report-analytics)
  - View: report/index — filter card, kartu ringkasan omzet/biaya/laba, tabel per-cabang, penjualan per kategori, kampanye, top produk & pelanggan; dusk selector lengkap untuk Dusk nanti
  - ROI penuh menunggu link discounts↔campaign (open question); sekarang hanya budget vs biaya

- **Dashboard Owner** (2026-08-25) — upgrade dari placeholder
  - DashboardController: omzet hari ini, P&L bulan ini, perbandingan cabang bulan ini (CSS progress bar — TANPA library chart), alerts stok menipis/habis + jadwal 7 hari ke depan tanpa staff
  - Scope: admin-cabang hanya data cabangnya & tidak melihat tabel perbandingan; owner = konsolidasi
  - View: dashboard/index rebuild row-cards + dusk selector (`card-revenue-today` dst)
  - Tests: DashboardTest ditambah (cards render, admin-cabang tanpa comparison table)

- **WhatsApp (Fonnte) + Email** (2026-08-25)
  - Migrations: `whatsapp_logs` (customer/transaction/schedule FK, phone, type, status queued|sent|failed, provider_ref, error_message), `email_logs` (serupa)
  - **Gotcha**: model `WhatsAppLog` → Laravel tebak tabel `whats_app_logs` (SALAH). Wajib set eksplisit `protected $table = 'whatsapp_logs'`
  - Service: `Domain/Notification/Services/WhatsAppService` + `EmailService` — pola konsisten: log `queued` dulu → dispatch job → job update status. SEMUA kirim lewat queue
  - Jobs: `SendWhatsAppMessage` (HTTP POST Fonnte, token `.env FONNTE_TOKEN`; tanpa token → failed + alasan tercatat; tries=3), `SendGmailInvoice` (Mail::to + markdown `emails/invoice`)
  - Trigger transaksi: status jadi `paid` (`TransactionService` → `notifyPaid`) → WA konfirmasi + email invoice ke pelanggan yg punya phone/email
  - Command: `schedule:remind {--dry-run}` — WA H-1/H-3 peserta jadwal `confirmed`, dedupe per peserta+jadwal+tipe; scheduler di `routes/console.php` daily 08:00
  - Halaman `/notifications` (permission baru `notifications.view`: owner/admin-cabang/finance): tabel log WA + email dgn badge status & error
  - Tests: NotificationTest 5 lulus (trigger paid→queue, fonnte no-token fail, fonnte success + Http::fake, remind dedupe, RBAC index)

- **BUG KRITIS: tombol aksi page-header tidak pernah tampil** (2026-08-25, dilaporkan owner)
  - Layout cek `$pageActions` (camelCase), semua view kirim `<x-slot:page_actions>` (snake_case) → variabel tidak pernah ada → tombol "+ Tambah/Catat" HILANG di SEMUA halaman index sejak awal (equipment, products, dst)
  - Lolos test selama ini karena Dusk test langsung `visit(route('x.create'))`, tidak pernah klik tombol header
  - Fix: normalisasi di layout (`$pageActions = $pageActions ?? $page_actions ?? null` + `! empty()`) — sekali fix, semua halaman beres. Regresi: assert `create-expense` di ExpenseTest
  - Fix lanjutan (root cause kedua): 6 view lama (inventory, discounts, equipment, product-categories, schedules, transactions) menaruh `<x-slot:page_actions>` DI DALAM `<x-ui.card>` → slot nyangkut di card, tidak pernah sampai layout. Semua dipindah jadi anak langsung `<x-layouts.app>`. Aturan: **slot `page_actions` WAJIB anak langsung layouts.app**, bukan card

- **Export Laporan CSV** (2026-08-25)
  - Route `GET /reports/export` (permission `reports.export`) — streamed CSV multi-section; bisa dibuka di Excel
  - `ReportService::$dateFrom/$dateUntil` public readonly untuk filename

- **Modul Booking — kamar / meeting room / camp site** (2026-08-25) — untuk use case resort (SIP Garden)
  - Migrations: `bookable_units` (branch, product FK, type room|meeting_room|camp_site, capacity, base_price), `bookings` (unit, customer?, transaction?, guest, date_start/end — end EKSKLUSIF, amount_total, status confirmed|checked_in|checked_out|cancelled + timestamp transisi)
  - Service: `Domain/Booking/Services/BookingService` — cek overlap server-side (`isAvailable`, cancelled tidak memblokir, ignoreBookingId utk update); validasi kapasitas; cancel = tanggal langsung bebas; check-in/out ber-audit
  - **Pembayaran lewat POS**: `recordPayment` auto-buat Transaction (idempotency_key `booking-{id}`, item produk unit qty=jumlah malam, backdate ke check-in 12:00) lalu split payment via TransactionService → laba-rugi tetap satu sumber data. Gotcha: transaksi dibuat SEBELUM cek overpay (transaksi kosong menunggu bayar itu by design)
  - Policy: BookingPolicy (update hanya saat confirmed; checkOut ability terpisah status checked_in; delete hanya cancelled)
  - Permissions baru `bookings.*`; owner semua, admin-cabang & kasir view/create/edit
  - Routes: resource + cancel/check-in/check-out/payments sebelum resource
  - Views: index (filter unit/status, badge lunas), create/edit/_form, show (4 kartu ringkasan, info tamu, form catat pembayaran dgn sisa otomatis, riwayat payment, form cek ketersediaan inline)
  - Seeder demo: **SipGardenDemoSeeder** — cabang "SIP Garden Resort" (brand lainnya): 3 kamar, meeting room, 2 camp site, 5 booking contoh (1 DP transfer, 1 meeting corporate lunas). Jalankan: `php artisan db:seed --class=SipGardenDemoSeeder`
  - Tests: BookingTest 9 lulus (blokir tanggal, checkout eksklusif, cancel membebaskan, kapasitas, payment→transaction partial→paid + backdate, overpay, alur check-in/out, RBAC kasir/guide, scope admin-cabang). Suite penuh 164 hijau

- **Kalender Booking + Sidebar bergrup + PDF** (2026-08-25)
  - **Kalender**: `/bookings/calendar` — grid unit × tanggal per bulan, sel hijau=terisi (klik → booking), kuning=check-in hari itu, navigasi bulan prev/next; filter cabang/tipe. Booking cancelled tidak menggambar sel.
  - **Sidebar bergrup konteks** (pola resmi Tabler `nav-item.dropdown`, dicek dari demo layout-fluid-vertical): Dashboard standalone; grup **Reservasi** (Booking, Kalender, Jadwal), **Penjualan** (POS, Transaksi, Pelanggan, Diskon), **Produk & Inventori**, **Keuangan** (Biaya, Gaji, Laporan), **Administrasi** (Cabang, Log Notifikasi). Grup otomatis `active` kalau salah satu anaknya aktif; grup kosong (tanpa permission) disembunyikan.
  - **PDF via barryvdh/laravel-dompdf** (keputusan dependency tercatat):
    - `/transactions/{id}/pdf` — e-receipt A5 (`pdf/receipt.blade.php`: brand cabang, item, PPN, split payment, status)
    - Email invoice otomatis melampirkan PDF transaksi (`SendGmailInvoice` → attachData)
    - `/reports/pdf` — rekap A4 (ringkasan omzet/biaya/laba + per cabang + kategori + top produk/pelanggan); tombol CSV+PDF di header Laporan
  - Gotcha: import facade `Response` bentrok dengan alias `Illuminate\Support\Facades\Response` di controller yang sama → alias `ResponseFacade`. Composer install yang timeout ternyata selesai tapi autoload belum di-dump → `composer dump-autoload` wajib setelah require lambat.
  - Tests: BookingCalendarTest 3 + PdfExportTest 4 lulus (PDF %PDF magic bytes, lampiran email, RBAC). Suite penuh **171/171 hijau**

- **Upload Bukti Transaksi (uang masuk & keluar)** (2026-08-25)
  - Migration: `expenses.proof_path`, `payments.proof_path` (nullable)
  - Upload: file `proof` — JPG/PNG/WebP/PDF maks 2 MB (`mimetypes` server-side), disimpan ke disk `public` folder `proofs/` (`php artisan storage:link` sudah dijalankan)
  - Uang KELUAR: form Biaya punya input bukti; edit bisa ganti bukti (file lama dihapus); index tampil ikon paperclip → buka bukti
  - Uang MASUK: form Catat Pembayaran di receipt transaksi + form pembayaran Booking — bukti tersimpan di payment; riwayat pembayaran receipt menampilkan paperclip
  - Tests: ProofUploadTest 4 lulus (upload expense, tolak .exe, upload payment, upload via booking). Suite penuh **175/175 hijau**

- **Tagihan / Invoice Pre-Payment** (2026-08-25)
  - Halaman **Tagihan** (`/transactions/invoices`, menu Penjualan): daftar transaksi `confirmed`/`partial` (belum lunas) dgn sisa tagihan + total piutang; aksi buka receipt & PDF
  - **Terbitkan Tagihan dari Booking**: tombol di halaman booking (status confirmed, belum ada transaksi) → buat transaksi TANPA pembayaran (backdate check-in), link ke booking, audit `invoice_issued`; idempotent via idempotency_key booking-{id}
  - **Kirim Email Tagihan**: tombol di receipt saat sisa >0 & pelanggan punya email → queue SendGmailInvoice dgn PDF terlampir
  - Alur korporat lengkap: Booking → Terbitkan Tagihan → kirim email/PDF → pelunasan via Catat Pembayaran (split) → status paid otomatis keluar dari daftar piutang
  - Tests: InvoiceTest 5 lulus. Suite penuh **180/180 hijau**

- **Dashboard Per Cabang Interaktif** (2026-08-25)
  - Route: `GET /dashboard/cabang/{branch}` (`dashboard.branch`, permission dashboard.view; admin-cabang 403 bila bukan cabangnya). Drill-down link dari tabel perbandingan cabang di dashboard utama.
  - Filter: preset Hari Ini/Minggu Ini/Bulan Ini/Bulan Lalu/Kustom (date range) + **toggle "Bandingkan periode sebelumnya"** (window sepanjang sama tepat sebelum periode — hati-hati `diffInDays` Carbon 3 return float, cast int) + switcher cabang cepat (badge link).
  - KPI cards via komponen baru `x-dashboard.kpi-card`: nilai besar, delta % (▲/▼ warna semantik), **sparkline SVG server-side** (tanpa library), klik card scroll ke chart terkait.
  - Chart.js di-install via npm (`chart.js` lokal, tanpa CDN) + Alpine wrapper `resources/js/alpine/chartPanel.js` (config JSON dari server). Komponen `x-dashboard.chart-panel`.
    - Line chart tren harian Omzet vs Biaya vs Laba (`ReportService::dailySeries`)
    - Donut komposisi omzet per kategori (`categoryDistribution`)
    - Bar hari & jam tersibuk (`busiestPattern` — dow via PHP Carbon, jam via substr string agar lintas SQLite/MySQL)
  - Top produk + **margin terendah**: basis biaya = rata-rata `unit_cost` stock_movements 'in'; tanpa data biaya → tampil "—".
  - **Insight Otomatis** (rule PHP): delta omzet ≥5% dgn kategori pendorong; omzet naik tapi laba turun → warning biaya; bestseller stok menipis/habis → danger restock.
  - Alert Operasional diperkaya: komponen `x-dashboard.alert-item` (details/summary expandable, badge urgensi merah/kuning, tombol aksi cepat Stok Masuk / Assign Staf).
  - Tabel 10 transaksi terakhir + search ringan Alpine (x-model q) + "Lihat semua" dengan filter cabang ter-apply.
  - Export PDF/CSV memakai route reports existing dengan param branch_id+range.
  - Tests: BranchDashboardTest 5 lulus. Suite penuh **185/185 hijau**
  - Lanjutan (permintaan owner): menu sidebar **"Dashboard Cabang"** tepat di bawah Dashboard → halaman pemilih cabang (`GET /dashboard/cabang`, `BranchDashboardController@index` + view `dashboard/branches`) berisi kartu omzet/laba per cabang + tombol buka dashboard cabang.

- **Buku Panduan Pengguna** (2026-08-25)
  - `docs/06-USER-GUIDE.md` — panduan lengkap semua modul per langkah UI (login, role, booking+kalender, jadwal, POS, tagihan/invoice, bukti upload, biaya, payroll, laporan, notifikasi, troubleshooting). Bahasa Indonesia, untuk staf non-teknis.

- **Refactor UI Tabler penuh + audit blade** (2026-08-24)
  - Layout mengikuti 100% `layout-fluid-vertical.html`: `body.layout-fluid` → sidebar `navbar-vertical` (user menu DI DALAM sidebar, tanpa top header) → `page-header` (page-pretitle/page-title + slot page_actions) → `page-body container-xl` → footer. Catatan: demo resmi pakai `container-xl` (bukan container-fluid) meski nama layout "fluid" — fluid-nya pada sidebar full-height
  - Warna brand via CSS variable Tabler (`--tblr-primary`), bukan override per elemen; sidebar pakai tema gelap default Tabler
  - Icon: `@tabler/icons-webfont` lokal via Vite (`ti ti-*`), tanpa CDN; glyph unicode (✕ − ☰) diganti SVG/ti-icon
  - Komponen baru/berubah: `x-ui.badge` (kontras otomatis: bg terang→text-dark, gelap→text-white), `x-ui.card` prop `:padded="false"` (pola card > table-responsive > table.card-table.table-vcenter), `x-ui.money` (input nominal + thousand separator id-ID, hidden input digit mentah ke server — server tak pernah parsing format)
  - Semua index: paginasi (`paginate(20)` + `links()` di card-footer), filter bar di `card-header`, tombol Tambah pindah ke page-header
  - Dashboard: `row row-deck row-cards` col-sm-6 col-lg-4 + card-subtitle
  - POS: grid produk = kartu (`row-cards`), panel kanan card-header/body/footer, CTA btn-primary; quick-add produk + Pelanggan + Jadwal pakai **Tom Select** (searchable); grid produk: search box + paginasi client-side (perPage bisa diubah)
  - Tom Select gotcha: pakai skin bawaan `tabler.css` (JANGAN import tom-select.bootstrap5.css — caret dobel + select asli tidak tersembunyi); butuh `tom-select/dist/css/tom-select.default.css` untuk CSS struktural; `.ts-dropdown` z-index 1050; card Tabler `overflow:hidden` memotong dropdown → override `overflow:visible` untuk kartu POS
  - Dusk: interaksi Tom Select via XPath `select[@dusk=...]/following-sibling::div[contains(@class,'ts-wrapper')]/div[contains(@class,'ts-control')]`; dropdown terbuka = wrapper dapat class `dropdown-active`
  - Field nominal uang (separator id-ID, simpan aman): POS diskon + baris pembayaran, produk base_price, diskon value, biaya maintenance, catat pembayaran di receipt
  - **Koordinasi multi-sesi Dusk**: Dusk berbagi port 8000 + `database/dusk.sqlite` — DUA sesi yang menjalankan dusk bersamaan menyebabkan sqlite malformed & stale element. Aturan: satu dusk pada satu waktu; PHPUnit aman paralel (sqlite :memory:). Zombie `php -S` dari dusk wajib dibunuh sebelum run baru

## Pertanyaan / Keputusan Terbuka
- [x] ~~Konfirmasi styling: Bootstrap 5 vs Tabler UI~~ → **TABLER UI** (owner, 2026-08-25): standard UI Tabler wajib, lihat `docs/05-UI-STANDARD.md`. Halaman existing (Bootstrap 5) perlu dimigrasi bertahap.
- [x] ~~Tax management (PPN Indonesia)~~ → **DIAKTIFKAN** (owner, 2026-08-24): PPN 11% server-side, kolom tax_total ada di transactions
- [ ] Apakah `tulambenscuba.com` dan `scubago.id` adalah 2 brand di 1 grup usaha yang sama (branch), atau 2 entitas terpisah yang perlu laporan keuangan sepenuhnya independen?
- [x] ~~Rate komisi guide/instruktur~~ → **DIPUTUSKAN** (owner, 2026-08-25): komisi per-user configurable (`commission_type`: per_pax/per_trip/none + `commission_rate` di users). Sisa open: komisi % harga paket DITUNDA (definisi "pendapatan trip" ambigu karena DP/split payment) — tambahkan nanti kalau dibutuhkan.
- [ ] Kebijakan denda kerusakan/hilang alat sewa: nominal tetap atau berdasarkan harga alat?
- [ ] Mekanisme alokasi unit alat ke jadwal (ERD tidak definisikan; sementara equipment_unit_id ada di transaction_items)
- [ ] Apakah admin-cabang boleh void transaksi? (sekarang owner-only sesuai matrix permission)
- [x] ~~Export laporan PDF~~ → **SELESAI** (2026-08-25): Dompdf terpasang; PDF receipt transaksi + lampiran email invoice + PDF rekap laporan. Excel native (Maatwebsite) tetap open — CSV sudah tersedia.
- [ ] ROI marketing penuh: butuh link discounts↔marketing_campaigns (kolom campaign_id di discounts?) — definisi atribusi omzet per kampanye belum diputuskan
- [ ] Omzet laporan hanya menghitung status `paid` (ikuti System Design §4). Transaksi `partial` (DP belum lunas) belum masuk omzet — perlu konfirmasi apakah DP dihitung pro-rata
- [ ] Gmail API OAuth2 resmi (PRD 5.2): kredensial OAuth belum ada. Saat ini email jalan via mailer pluggable `.env MAIL_MAILER` (SMTP/log) — swap ke Google API client tinggal ganti transport, tanpa ubah job/service
- [ ] FONNTE_TOKEN belum diisi di .env produksi — sampai diisi, WA log akan berstatus `failed` dengan alasan jelas
- [ ] Dusk test Payroll + Report + Dashboard + Notification ditunda oleh owner sampai semua fitur selesai (selector dusk sudah disiapkan; test Dusk Payroll sudah ditulis tapi belum hijau)

## Log Perubahan Arsitektur/Keputusan Penting
_(catat di sini jika ada keputusan yang menyimpang dari `02-SYSTEM-DESIGN.md`, beserta alasannya)_
- **2026-08-25** — **Email transport**: PRD meminta Gmail API OAuth2 (Google API PHP Client). Implementasi sekarang pakai **Laravel Mailer pluggable** (`MAIL_MAILER` di .env; SMTP/log) dengan job + log yang sama. Deviasi karena kredensial OAuth belum tersedia — arsitektur sengaja dipisah: ganti transport nanti TIDAK menyentuh `SendGmailInvoice`/`EmailService`. Terbuka di open questions.
- **2026-08-25** — **Semua modul MVP 4.x sudah dibangun** (Branch→Notification). Sisa kerja: Dusk suite tertunda, Gmail API OAuth, PDF export, dan open questions bisnis.
- **2026-08-25** — **UI framework resmi: Tabler UI** (`docs/05-UI-STANDARD.md`). Semua Blade baru/wajib ikut struktur layout Tabler (`page` > `navbar-vertical` + `page-wrapper`), class Tabler bawaan, Tabler Icons. Halaman lama berbasis Bootstrap 5 dimigrasi bertahap mengikuti standar ini.
- **2026-08-24** — Aset frontend **semua lokal**: Bootstrap 5 + Alpine.js via npm/Vite, tanpa CDN. Tercatat di System Design bagian 1 & PRD bagian 6.
- **2026-08-24** — Modul baru **Inventory/Stok (4.14)**: kartu stok (`stock_movements`), stok keluar otomatis dari POS, opname, alert stok rendah, nilai persediaan sederhana.
- **2026-08-24** — Pengujian: **Laravel Dusk wajib untuk semua fitur** (browser test alur UI utama per modul), selector via atribut `dusk` dari reusable components. System Design bagian 9 + AI Guide DoD.
- **2026-08-24** — UI wajib dibangun dari **reusable Blade components** (`resources/views/components/ui/*`) + Alpine composable (`resources/js/alpine/*`) agar konsisten antar modul. System Design bagian 7.2.
- **2026-08-24** — Auth pakai **custom LoginController** bukan Laravel Breeze (Breeze Blade stack pakai Tailwind → konflik dgn larangan Tailwind di PRD bagian 6).
- **2026-08-24** — Layout pakai **anonymous Blade components** (`components/layouts/app.blade.php`, `components/layouts/guest.blade.php`) bukan `resources/views/layouts/tablet.blade.php` — lebih konsisten dgn reusable components rule.
- **2026-08-24** — **Internationalization (i18n)**: Default locale `id`, supported `id`/`en`, semua string UI gunakan `__()` helper, language switcher di navbar. PRD tidak spesifik soal bahasa, tapi konteks Bali (wisatawan asing) → bilingual wajib.
- **2026-08-24** — **Customer Mini CRM**: tambah `nationality_type` (indonesia/international) untuk personalisasi template email/WA, `preferences` (JSON) untuk alergi/ukuran alat/level pengalaman. Show view untuk profile customer.
