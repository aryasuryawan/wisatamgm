# PRD — Sistem Informasi Manajemen Tulamben Scuba & ScubaGo
Versi 1.0 | Disusun sebagai bahan MVP | Owner: Tulamben Scuba (tulambenscuba.com) & ScubaGo (scubago.id)

## 1. Latar Belakang

Tulamben Scuba & ScubaGo adalah bisnis wisata penyelaman dan bahari yang menjual paket wisata, jasa (kursus selam, guide), makanan, sewa alat selam, transportasi, dan merchandise. Bisnis berjalan multi-cabang/multi-brand (2 domain saat ini) dan Owner belum punya visibilitas real-time atas profitabilitas per cabang, biaya operasional (termasuk marketing), dan performa penjualan. Operasional harian (booking, transaksi, jadwal trip, sewa alat) masih belum tersentralisasi dalam satu sistem.

## 2. Tujuan Produk

1. Owner bisa melihat **untung/rugi** bisnis secara real-time, per cabang, per kategori produk.
2. Operasional harian (transaksi, jadwal, sewa alat, pelanggan) tercatat di satu sistem, dioperasikan dari **tablet** oleh staf di lapangan/kasir.
3. Owner bisa membandingkan performa antar cabang (branch benchmarking).
4. Semua biaya (operasional + marketing) tercatat dan bisa ditelusuri ke ROI.
5. Komunikasi ke pelanggan (booking confirm, reminder, invoice) otomatis via WhatsApp (Fonnte) & Email (Gmail sender).
6. Penggajian staf & komisi guide/freelance terhitung otomatis dari data transaksi/jadwal.

## 3. Target Pengguna (Roles)

| Role | Deskripsi | Akses Utama |
|---|---|---|
| Owner / Super Admin | Pemilik bisnis, lintas cabang | Semua modul, dashboard konsolidasi |
| Admin Cabang / Manager | Penanggung jawab 1 cabang | Semua modul di cabang miliknya |
| Kasir / Front Desk | Input transaksi harian | POS, booking, pelanggan |
| Dive Guide / Instruktur | Pelaksana trip | Jadwal miliknya, checklist alat, absensi peserta |
| Finance / Bookkeeper | Pencatat keuangan | Biaya, laporan keuangan, payroll |
| Marketing | Pengelola campaign | Input biaya marketing, kode promo, laporan sumber booking |

Catatan: Sistem menggunakan RBAC (role & permission), bukan role hardcode, agar fleksibel menambah role baru.

## 4. Ruang Lingkup Modul (MVP)

### 4.1 Autentikasi & Manajemen Cabang
- Login multi-role, 1 user bisa ditugaskan ke 1+ cabang.
- CRUD cabang (nama, domain/brand terkait, alamat, PIC, status aktif).
- Semua data transaksional terikat `branch_id` untuk isolasi & perbandingan performa.

### 4.2 Manajemen Pelanggan (Mini CRM)
- Data pelanggan: nama, kontak (WA/email), sumber (organik/iklan/referral), tag/segmen.
- Riwayat sertifikasi selam (level PADI/SSI, no. sertifikat, tanggal, expiry jika relevan).
- Riwayat transaksi & jadwal trip yang pernah diikuti.
- Catatan preferensi (alergi makanan, ukuran alat, level pengalaman).
- Auto-tagging pelanggan (baru / repeat / VIP) berdasarkan histori transaksi.

### 4.3 Katalog Produk & Kategori Penjualan
Kategori produk (polymorphic, satu struktur produk untuk semua):
- **Wisata** — paket trip (harga per pax, kapasitas, itinerary singkat, kebutuhan alat).
- **Jasa** — kursus sertifikasi, jasa guide privat, foto/video underwater.
- **Makanan & minuman**.
- **Sewa alat** — per kategori alat (BCD, regulator, wetsuit, tank, fins, dive computer, dll), harga per unit/hari.
- **Transportasi** — antar-jemput, sewa kendaraan/boat.
- **Merchandise** — barang fisik dengan stok.
- Setiap produk: harga dasar, harga per cabang (opsional override), status aktif, kategori, satuan.

### 4.4 Penyewaan Alat (Equipment Rental)
- Setiap unit alat punya kode/serial unik, kondisi (baik/rusak/servis), status (tersedia/disewa/maintenance).
- Kalender ketersediaan alat agar tidak double booking.
- Log peminjaman & pengembalian (kondisi saat kembali, denda jika rusak/hilang).
- Riwayat maintenance per unit alat.

### 4.5 Jadwal (Scheduling)
- Jadwal trip/kelas: tanggal, kapasitas, guide/instruktur bertugas, alat yang dialokasikan, cabang penyelenggara.
- Pendaftaran peserta ke jadwal (link ke transaksi & pelanggan).
- Status jadwal: draft, terkonfirmasi, berjalan, selesai, dibatalkan.
- Notifikasi otomatis (WA/email) ke peserta H-1 / H-3 (reminder).

### 4.6 Transaksi / POS (Tablet-first)
- UI ringkas untuk tablet: pilih pelanggan → pilih produk (multi kategori dalam 1 transaksi) → alokasi jadwal/alat bila relevan → diskon → pembayaran.
- Multi metode pembayaran & split payment, DP/pelunasan (booking wisata sering pakai DP).
- Cetak/kirim invoice (PDF) otomatis via WA/email.
- Retur/void transaksi dengan approval role tertentu (audit trail wajib).

### 4.7 Diskon & Promo
- Tipe diskon: nominal, persentase, kode voucher, diskon member/repeat customer.
- Batas pemakaian (per pelanggan, per periode, per kategori produk), tanggal berlaku.
- Tracking pemakaian diskon per campaign (untuk analisis ROI marketing).

### 4.8 Keuangan
- Pencatatan pemasukan (otomatis dari transaksi) & pengeluaran manual (kategori: operasional, alat, gaji, **marketing**, sewa tempat, lain-lain).
- Laporan laba rugi per cabang & konsolidasi, per periode (harian/mingguan/bulanan/custom range).
- Cash flow sederhana (kas masuk vs keluar).

### 4.9 Penggajian (Payroll)
- Gaji tetap staf per cabang.
- Komisi guide/instruktur berbasis jadwal yang di-handle (rate per trip/pax, bisa dikonfigurasi).
- Slip gaji per periode, rekap payroll sebagai bagian dari biaya operasional (masuk ke laporan laba rugi).

### 4.10 Laporan (Reporting)
- Laporan penjualan berkala per kategori produk, per cabang, per staf.
- Laporan profitabilitas (pendapatan − biaya termasuk marketing) per cabang, dibandingkan antar cabang.
- Produk/trip terlaris, pelanggan dengan nilai transaksi tertinggi (top customer).
- ROI marketing sederhana (biaya campaign vs pendapatan dari kode promo/sumber terkait).
- Semua laporan bisa difilter tanggal & cabang, export ke Excel/PDF.

### 4.11 Dashboard Owner
- Ringkasan real-time: omzet hari ini/bulan ini, laba estimasi, perbandingan antar cabang (grafik), alert (mis. stok alat rendah, jadwal belum ada guide).

### 4.12 Integrasi WhatsApp (Fonnte)
- Kirim: konfirmasi booking, invoice, reminder jadwal, notifikasi pembayaran.
- Log semua pesan terkirim (status delivered/failed) untuk audit.

### 4.13 Integrasi Email (Gmail Apps Sender)
- Kirim invoice/receipt & email marketing dasar (broadcast sederhana ke segmen pelanggan) via Gmail API (OAuth, bukan SMTP polos, agar reliabel & terdeteksi resmi).

### 4.14 Manajemen Inventori (Stok)
- Stok merchandise & barang jual dikelola **per cabang** dengan kartu stok (stock movement log): setiap masuk/keluar/penyesuaian tercatat siapa-kapan-dari mana.
- Stok keluar otomatis dari transaksi POS (produk bertipe merchandise/makanan); stok masuk dari pembelian/penerimaan manual.
- **Stok opname**: penyesuaian stok hasil hitung fisik, dengan selisih tercatat sebagai adjustment ber-audit.
- Alert stok rendah (minimum stock per produk per cabang) muncul di dashboard Owner/Admin cabang.
- Nilai persediaan sederhana (qty × harga belan terakhir / biaya rata-rata) untuk keperluan laporan.

## 5. Kebutuhan Non-Fungsional
- **UI tablet-first**: touch target besar, minim ketikan (dropdown/search), layout landscape-friendly, responsif ke desktop juga.
- **Multi-cabang**: seluruh query laporan default terfilter cabang sesuai akses user; Owner bisa lihat semua.
- **Audit trail**: setiap transaksi/void/edit harga tercatat siapa & kapan.
- **Performa**: laporan bulanan untuk 1 cabang harus render < 3 detik pada data 1 tahun.
- **Keamanan**: role & permission granular, password policy, token API integrasi disimpan terenkripsi.
- **Ketersediaan integrasi**: kegagalan kirim WA/email tidak boleh menggagalkan transaksi (dikerjakan via queue/job async, retry).

## 6. Batasan Teknis (Constraint dari Owner)
- Backend: **Laravel** (framework penuh, bukan Lumen).
- Interaktivitas frontend: **Alpine.js** — **tidak boleh Livewire**.
- Styling: **tidak boleh Tailwind CSS** → keputusan: gunakan **Bootstrap 5** (atau design system CSS kustom) sebagai default asumsi; perlu konfirmasi Owner/tim jika ada preferensi lain.
- **Semua aset frontend (JS/CSS/font) harus lokal** — di-bundle via Vite, **dilarang memuat dari CDN** (reliabilitas jaringan cabang + keamanan).
- UI dioptimalkan untuk **tablet** (kasir/lapangan), bukan mobile phone kecil.
- Rendering view: Blade (server-rendered) + Alpine.js untuk interaksi dinamis (bukan SPA penuh).
- UI dibangun dari **reusable Blade components** sebisa mungkin agar tampilan & perilaku konsisten antar modul (lihat System Design bagian 7).

## 7. MVP vs Fase Berikutnya

**MVP (Fase 1)** — modul 4.1 s.d. 4.13 di atas, skala minimal fungsional untuk 2 cabang.

**Fase 2 (bukan MVP)**:
- App mobile native untuk guide (saat ini cukup web responsif tablet).
- Marketing automation lanjutan (drip email, segmentasi otomatis lanjutan).
- Integrasi channel booking OTA (Traveloka, Klook, dll).
- Modul inventori merchandise lanjutan (multi-gudang, PO ke supplier).
- BI/analytics lanjutan (forecasting).

## 8. Metrik Sukses (KPI Produk)
- Owner dapat melihat laporan laba/rugi per cabang dalam < 5 klik dari dashboard.
- 100% transaksi tercatat dengan cabang & kategori produk yang benar.
- Waktu input 1 transaksi POS di tablet < 60 detik untuk kasus umum.
- Notifikasi WA/email terkirim otomatis untuk ≥ 95% transaksi valid.

## 9. Out of Scope (MVP)
- Payment gateway online (pembayaran tetap dicatat manual/offline oleh kasir di MVP; integrasi payment gateway bisa fase berikut).
- Aplikasi terpisah untuk pelanggan (self-booking portal) — MVP fokus ke sisi internal/operasional.
