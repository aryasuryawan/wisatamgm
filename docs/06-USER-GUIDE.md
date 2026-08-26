# Buku Panduan Pengguna — Sistem Manajemen Tulamben Scuba, ScubaGo & SIP Garden

Panduan lengkap penggunaan aplikasi untuk staf harian. Semua fitur dijelaskan
langkah demi langkah sesuai menu di aplikasi.

---

## Daftar Isi

1. [Masuk Aplikasi](#1-masuk-aplikasi)
2. [Peran & Hak Akses](#2-peran--hak-akses)
3. [Dashboard](#3-dashboard)
4. [Reservasi — Booking Kamar / Meeting Room / Camp Site](#4-reservasi--booking)
5. [Kalender Booking](#5-kalender-booking)
6. [Jadwal Trip / Kelas](#6-jadwal-trip--kelas)
7. [Penjualan — Kasir (POS)](#7-penjualan--kasir-pos)
8. [Invoice & Tagihan](#8-invoice--tagihan)
9. [Transaksi — E-Receipt, PDF, Bukti Bayar](#9-transaksi--e-receipt-pdf-bukti-bayar)
10. [Pelanggan (Mini CRM)](#10-pelanggan-mini-crm)
11. [Diskon & Promo](#11-diskon--promo)
12. [Produk & Kategori](#12-produk--kategori)
13. [Unit Alat Selam & Maintenance](#13-unit-alat-selam--maintenance)
14. [Stok Barang](#14-stok-barang)
15. [Keuangan — Catat Pengeluaran Uang (+ Upload Bukti)](#15-keuangan--catat-pengeluaran-uang)
16. [Kampanye Marketing](#16-kampanye-marketing)
17. [Payroll / Gaji](#17-payroll--gaji)
18. [Laporan](#18-laporan)
19. [Log Notifikasi (WhatsApp & Email)](#19-log-notifikasi)
20. [Administrasi Cabang](#20-administrasi-cabang)
21. [Tips & Masalah Umum](#21-tips--masalah-umum)

---

## 1. Masuk Aplikasi

1. Buka alamat aplikasi (mis. `http://localhost:8000` atau domain produksi).
2. Masukkan **email** dan **password** akun Anda.
3. Gunakan tombol **ID / EN** di sidebar untuk mengganti bahasa (Indonesia/Inggris).
4. Menu pengguna (nama Anda di sidebar atas) → **Keluar** untuk logout.
5. Salah password 10x dalam 1 menit → akun dikunci sementara, tunggu sebentar.
6. Akun nonaktif tidak bisa login — hubungi owner/admin.

## 2. Peran & Hak Akses

| Peran | Bisa apa |
|---|---|
| **Owner** | Semua modul semua cabang, approve payroll, void transaksi |
| **Admin Cabang** | Operasional penuh HANYA di cabangnya |
| **Kasir** | POS, pelanggan, jadwal, booking |
| **Dive Guide** | Lihat jadwal/pelanggan/alat saja |
| **Finance** | Biaya, payroll, laporan, stok, log notifikasi |
| **Marketing** | Diskon/kupon, kampanye, laporan |

Menu yang muncul di sidebar otomatis menyesuaikan hak akses Anda.
Semua data uang selalu tervalidasi ulang di server — tidak bisa "diakali" dari browser.

## 3. Dashboard

Menu **Dashboard** menampilkan:
- **Omzet hari ini** & jumlah transaksi lunas
- **Omzet / Biaya / Estimasi Laba bulan berjalan**
- **Perbandingan cabang** bulan ini (bar omzet + laba) — khusus owner
- **Alert**: stok menipis, stok habis, jadwal ≤7 hari tanpa guide/staf
- Daftar cabang tempat Anda bertugas

Klik alert untuk langsung membuka halaman terkait (stok/jadwal).

---

## 4. Reservasi — Booking

Untuk kamar, meeting room, camp site (berlaku juga untuk SIP Garden).

### Membuat booking baru
1. **Reservasi → Booking → + Buat Booking**
2. Pilih **unit** (tampil harga per malam/hari), isi nama tamu, telepon.
3. Tentukan **Check-in** dan **Check-out**. *Tanggal check-out bersifat eksklusif*:
   tamu lain BOLEH check-in pada tanggal check-out Anda.
4. Isi jumlah orang (**maks = kapasitas unit**), **Total Harga**, pilih data pelanggan bila ada.
5. Simpan → tanggal langsung **diblokir**, tidak bisa dibooking ganda.

Sistem menolak otomatis jika: rentangan bentrok dengan booking lain, kapasitas
terlampaui, atau unit nonaktif.

### Siklus status booking
```
Terbooking (confirmed) → Menginap (checked-in) → Selesai (checked-out)
        ↘ Dibatalkan (tanggal langsung bebas lagi)
```

### Menerima pembayaran booking
Di halaman detail booking (status bukan Dibatalkan):
- Isi metode bayar + nominal (sisa tagihan terisi otomatis) + upload **bukti** → **Catat Pembayaran**.
- Boleh dicicil (DP lalu pelunasan) — setiap pembayaran tercatat sebagai baris terpisah.
- Riwayat pembayaran tampil di bawah form; ikon 📎 membuka file bukti.

### Membatalkan
Tombol **Batalkan Booking** (konfirmasi) → unit tersedia kembali.
Booking yang sudah dibatalkan bisa dihapus permanen oleh owner.

---

## 5. Kalender Booking

**Reservasi → Kalender**

- Grid **unit × tanggal** untuk satu bulan; hijau = malam terisi, kuning = sedang menginap.
- Klik sel terisi untuk membuka detail booking-nya.
- Navigasi bulan dengan tombol ‹ sebelumnya / berikutnya ›.
- Filter cabang/tipe unit via dropdown atas (owner).

---

## 6. Jadwal Trip / Kelas

Untuk fun dive, kursus, island tour, outbound session.

### Membuat jadwal
1. **Reservasi → Jadwal → + Tambah Jadwal**
2. Isi cabang, produk (paket), tanggal & jam mulai/selesai, kapasitas maksimal.
3. Simpan. Status awal **Draft**.

### Mengelola peserta & staf
Buka detail jadwal:
- **Tambah peserta**: pilih pelanggan (tidak bisa dobel).
- **Tambah staf**: guide/instruktur/asisten/divemaster.
- Saat transaksi POS memuat item produk yang dikaitkan ke jadwal, peserta masuk otomatis.

### Alur status jadwal
```
Draft → Terkonfirmasi → Berjalan → Selesai
   ↘ Dibatalkan (dari draft/confirmed)
```
Jadwal **Selesai** adalah sumber komisi guide pada payroll.

> Reminder WhatsApp H-1/H-3 ke peserta dikirim otomatis jam 08:00 oleh server
> (butuh queue worker aktif — lihat §21).

---

## 7. Penjualan — Kasir (POS)

1. **Penjualan → Kasir (POS)**.
2. (Opsional) pilih pelanggan — cari via kotak pencarian.
3. Pilih tab kategori → klik produk untuk masuk keranjang. Atur qty bila perlu.
   - Item wisata/jasa dapat dikaitkan ke **jadwal** (dropdown di keranjang).
4. Kode diskon (bila ada) diisi di kolom diskon.
5. Cek ringkasan: Subtotal → Diskon → **PPN 11%** → Total.
6. **Bayar Sekarang**: pilih metode (tunai/transfer/QRIS/kartu). Split payment
   (bayar gabungan beberapa metode) didukung.
7. Upload foto/PDF **bukti bayar** bila ada.
8. Setelah lunas → e-receipt tampil; invoice email terkirim otomatis bila pelanggan punya email.

Perlindungan: harga SELALU diambil dari master produk (input browser diabaikan),
PPN dihitung server, double-submit dicegah dengan idempotency key.

## 8. Invoice & Tagihan

Untuk penjualan "bayar belakangan" (korporat/meeting room/booking).

### Menerbitkan tagihan dari booking
1. Buka detail **Booking** yang masih Terbooking dan belum ada transaksi.
2. Klik **Terbitkan Tagihan** → sistem membuat transaksi belum-dibayar
   (jumlah = harga × jumlah malam) dan mengaitkannya ke booking.
3. Kirim ke pelanggan:
   - Tombol **PDF** → unduh/buka invoice, lampirkan manual (WA dsb), ATAU
   - Tombol **Kirim Email Tagihan** (muncul bila pelanggan punya email) → email
     berisi invoice + **lampiran PDF** dikirim otomatis lewat antrian.

### Melunasi tagihan
1. Buka receipt transaksi dari daftar Tagihan.
2. **Catat Pembayaran** (boleh parsial/DP, upload bukti transfer).
3. Setelah sisa = 0 → status **paid** → hilang dari daftar piutang, masuk omzet.

### Memantau piutang
**Penjualan → Tagihan**: semua tagihan outstanding + **Total Piutang** di footer.
Tagihan yang sudah lunas otomatis keluar dari daftar.

### Invoice untuk walk-in tanpa POS?
Semua uang masuk harus lewat transaksi. Untuk penjualan non-kasir gunakan POS
metode apapun; untuk tagihan gunakan alur booking di atas.

## 9. Transaksi — E-Receipt, PDF, Bukti Bayar

Halaman **Penjualan → Transaksi** = arsip semua transaksi (filter status/cabang).

Detail transaksi (#000123):
- **E-receipt** siap cetak → tombol **Cetak** (print browser; bisa Save-as-PDF).
- **PDF** → unduh invoice resmi berlogo cabang (A5).
- **Catat Pembayaran tambahan** + upload bukti untuk transaksi parsial.
- Riwayat pembayaran: ikon 📁/📎 membuka file bukti.
- Owner: **Void** transaksi (stok & counter pelanggan dikembalikan, WAJIB audit).

## 10. Pelanggan (Mini CRM)

**Penjualan → Pelanggan**
- Tambah/edit: nama, telepon, email, cabang asal, sumber (organik/iklan/referral/walk-in),
  kewarganegaraan (lokal/internasional) — berguna untuk template WA/email nanti,
  segmen (VIP/Repeat/Baru), preferensi (alergi, ukuran alat, level sertifikasi).
- Sertifikasi selam (PADI/SSI, level, nomor, expiry) di halaman detail.
- Counter **total order & total belanja** naik otomatis saat transaksi lunas.

## 11. Diskon & Promo

**Penjualan → Diskon** (Marketing/Admin/Owner)
- Tipe: nominal (Rp) atau persen (%); opsional kode voucher.
- Batasan: periode berlaku, batas total pemakaian, batas per pelanggan, kategori produk.
- Pemakaian tercatat per transaksi → dipakai untuk analisis ROI kampanye.

## 12. Produk & Kategori

**Produk & Inventori → Produk / Kategori**
- Kategori bawaan: Wisata, Jasa, Makanan & Minuman, Sewa Alat, Transportasi, Merchandise.
- Produk: nama, kategori, cabang (opsional = global), harga dasar, satuan (pax/hari/pcs…),
  stok (untuk barang fisik), status aktif.
- Produk aktif langsung muncul di POS & form jadwal/booking.

## 13. Unit Alat Selam & Maintenance

**Produk & Inventori → Unit Alat**
- Tiap unit fisik punya **kode unik per cabang** (BCD-01…), kondisi, status.
- Status: Tersedia / Dipinjam / Maintenance.
- Tab **Maintenance Log** di halaman edit unit: catat servis rutin/perbaikan/inspeksi/
  penggantian + biaya + teknisi.

## 14. Stok Barang

**Produk & Inventori → Stok** — kartu stok untuk barang fisik (bukan alat sewa per-unit).
- **Stok Masuk**: pembelian/restock (isi qty & biaya satuan opsional).
- **Opname/Adjustment**: koreksi hasil hitung fisik.
- Stok **keluar otomatis** setiap ada penjualan merchandise/makanan via POS.
- Alert stok rendah (≤5) & habis (=0) tampil di Dashboard.

## 15. Keuangan — Catat Pengeluaran Uang

**Keuangan → Biaya**

### Mencatat pengeluaran (bensin, galon air, listrik, pajak mobil, dll)
1. **+ Catat Pengeluaran Uang**
2. Pilih **cabang**, **kategori** (Operasional/Alat/Gaji/Marketing/Sewa Tempat/Lainnya).
3. Isi deskripsi jelas (mis. "Bensin boat 3 Juni"), nominal, tanggal.
4. Opsional: kaitkan ke **kampanye marketing** (untuk ROI iklan).
5. **Upload bukti**: foto kuitansi / struk transfer / PDF (JPG/PNG/WebP/PDF, maks 2 MB).
6. Simpan. Di daftar, ikon 📎 membuka bukti; ikon "Otomatis" = dibuat sistem (payroll).

Biaya hasil generate payroll **terkunci** — tidak bisa diedit/dihapus manual agar
laba-rugi konsisten. Ganti lewat modul Gaji.

### Edit / hapus
Tombol Edit/Hapus tersedia untuk biaya manual (hanya role berizin). Setiap perubahan
uang tercatat di audit log.

## 16. Kampanye Marketing

**Keuangan → Biaya → tombol Kampanye**
- Buat kampanye: nama, channel (Meta/Google/IG/TikTok/Flyer), budget, periode, cabang.
- Hubungkan biaya iklan ke kampanye saat mencatat pengeluaran.
- Halaman kampanye menampilkan **budget vs terpakai** + progress bar + badge over-budget.
- Rekap juga tampil di Laporan (§18).

## 17. Payroll / Gaji

**Keuangan → Gaji** (Finance membuat, Owner approve)

### Setup sekali (per staf)
Isi di data user: **Gaji Pokok** (staf tetap) dan/atau **Komisi**:
- `per_pax` → Rp × jumlah peserta trip yang ditangani
- `per_trip` → Rp × jumlah trip yang ditangani
- `none` → tanpa komisi

### Proses gaji bulanan
1. **Buat Periode**: pilih cabang + rentang tanggal (mis. 1–31 Juli). Sistem menolak
   periode yang tumpang tindih.
2. **Generate Item**: komisi dihitung dari jadwal SELESAI dalam periode; staf tetap
   cabang ikut digaji walau tidak handle trip.
3. Koreksi **potongan** per staf bila perlu (masih boleh selama Draft).
4. Owner klik **Setujui** → item dikunci.
5. Owner klik **Tutup Periode** → total otomatis menjadi **Biaya kategori Gaji**
   (badge Otomatis) → langsung mengurangi laba-rugi. Slip per staf terlihat di tabel periode.

## 18. Laporan

**Keuangan → Laporan**
- Filter: **cabang** + rentang **tanggal** (default bulan berjalan).
- Ringkasan: Omzet (transaksi lunas) · Biaya · Estimasi Laba.
- Tabel: perbandingan antar-cabang, penjualan per kategori, kampanye (budget vs terpakai),
  top 5 produk, top 5 pelanggan.
- Export:
  - **CSV** → dibuka di Excel;
  - **PDF** → rekap siap cetak/lampir laporan bulanan.

Definisi penting: **omzet hanya dihitung dari transaksi berstatus LUNAS (paid)**.
Tagihan outstanding dan DP belum masuk omzet sampai dilunasi.

## 19. Log Notifikasi

**Administrasi → Log Notifikasi**
- Tabel kiri: semua **WhatsApp** (via Fonnte) — status Menunggu/Terkirim/Gagal + alasan error.
- Tabel kanan: **Email** — idem.
- Pesan gagal karena konfigurasi (token Fonnte kosong / mailer salah) akan menjelaskan
  penyebabnya di kolom status — laporkan ke admin server.

## 20. Administrasi Cabang

**Administrasi → Cabang** — data cabang/brand (nama, domain, alamat, PIC, aktif).
Satu user bisa ditugaskan ke beberapa cabang. Semua modul lain otomatis mengikuti
cabang ini (multi-brand: Tulamben Scuba, ScubaGo, SIP Garden Resort, dst).

## 21. Tips & Masalah Umum

**WA/email tidak terkirim?**
Pesan tertahan di antrian. Server harus menjalankan `php artisan queue:work`
dan scheduler (`php artisan schedule:work` / cron). WA butuh `FONNTE_TOKEN` di
`.env`; tanpa token status akan "Gagal" dengan alasan jelas di Log Notifikasi.

**Lupa password / akun lockout?**
Tunggu 1 menit (batas 10 percobaan) atau minta admin reset.

**Angka laporan beda dengan harapan?**
Pastikan filter tanggal benar; ingat omzet hanya menghitung transaksi LUNAS;
biaya termasuk payroll otomatis (badge Otomatis di halaman Biaya).

**Double booking takut salah?**
Tidak bisa — sistem menolak rentangan bentrok. Cek visual lewat Kalender Booking.

**Butuh kategori biaya baru?**
Saat ini 6 kategori bawaan. Ajukan ke admin/developer (ditambah via seeder).

**Hapus salah?**
Hampir semua hapus butuh konfirmasi & tercatat di audit log. Void transaksi hanya
owner dan mengembalikan stok secara otomatis.

---

*Dokumen: `docs/06-USER-GUIDE.md` · diperbarui 2026-08-25*
