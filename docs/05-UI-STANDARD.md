# UI Standard — Tabler UI (Wajib Dibaca Sebelum Membuat/Edit UI Apapun)

Dokumen ini adalah aturan tetap untuk semua pekerjaan frontend di project ini.
Berlaku untuk siapapun/apapun yang mengedit Blade/HTML/CSS di project ini,
termasuk AI coding agent. **Jangan menyimpang dari aturan ini tanpa alasan
eksplisit dari user.**

## 0. Rujukan Wajib

Referensi visual/struktur resmi: **https://preview.tabler.io/layout-fluid-vertical.html**

Sebelum membuat halaman/komponen baru, fetch/cek demo resmi tersebut (atau
demo Tabler lain yang relevan di https://preview.tabler.io/) untuk melihat
markup yang benar. **Jangan mengarang struktur class Tabler dari ingatan** —
kalau ragu, cek dokumentasi/demo dulu.

## 1. Prinsip Utama

- Gunakan **class Tabler bawaan**, jangan bikin CSS custom kecuali untuk hal
  yang memang tidak disediakan Tabler (misal brand color spesifik).
- Kalau butuh warna brand custom, override lewat CSS variable
  (`--tblr-primary`, dst), **jangan** override manual per-elemen
  (`style="background:..."` atau class ad-hoc yang menimpa Tabler).
- Konsistensi lintas halaman lebih penting daripada kreativitas per-halaman.
  Semua halaman list harus terlihat seperti "keluarga" yang sama.
- Jangan pernah submit/selesai kalau kontras teks-background tidak lolos cek
  visual dasar (badge hijau-di-atas-hijau, dsb adalah bug, bukan gaya).

## 2. Struktur Layout Wajib

Semua halaman harus mengikuti kerangka ini (adaptasi dari
`layout-fluid-vertical`):

```html
<div class="page">
  <aside class="navbar navbar-vertical navbar-expand-lg navbar-transparent">
    <!-- navbar-nav > nav-item > nav-link (nav-link-icon + nav-link-title) -->
    <!-- item aktif WAJIB pakai class "active" -->
  </aside>

  <div class="page-wrapper">
    <div class="page-header d-print-none">
      <div class="container-fluid">
        <div class="row g-2 align-items-center">
          <div class="col">
            <div class="page-pretitle">[Nama modul, mis. "Manajemen"]</div>
            <h2 class="page-title">[Judul halaman]</h2>
          </div>
          <div class="col-auto ms-auto d-print-none">
            <!-- action button utama, mis. "+ Tambah", ada di sini -->
          </div>
        </div>
      </div>
    </div>

    <div class="page-body">
      <div class="container-fluid">
        <!-- konten halaman -->
      </div>
    </div>
  </div>
</div>
```

Aturan container: gunakan `container-fluid` di semua wrapper (bukan
`container-xl`) karena layout project ini fluid. Jangan campur keduanya di
halaman berbeda.

## 3. Komponen Wajib per Kasus

### Card
```html
<div class="card">
  <div class="card-header">
    <h3 class="card-title">[Judul]</h3>
  </div>
  <div class="card-body">...</div>
</div>
```
Semua "kotak informasi" (dashboard summary, grid produk, panel ringkasan)
HARUS pakai `.card`, tidak boleh div polos berborder manual.

Grid multi-card pakai:
```html
<div class="row row-deck row-cards">
  <div class="col-sm-6 col-lg-4">...card...</div>
</div>
```

### Tabel List
```html
<div class="card">
  <div class="table-responsive">
    <table class="table card-table table-vcenter text-nowrap datatable">
      <thead><tr><th>...</th></tr></thead>
      <tbody>...</tbody>
    </table>
  </div>
</div>
```
Filter bar (search/dropdown filter) ditaruh di `card-header` di atas tabel
ini, bukan sebagai elemen lepas di luar card.

### Badge Status
Selalu pakai kombinasi bg + text yang kontras dan sudah terbukti readable:
```html
<span class="badge bg-success text-white">Aktif / Tersedia</span>
<span class="badge bg-secondary text-white">Nonaktif / Kosong</span>
<span class="badge bg-warning text-dark">Pending</span>
<span class="badge bg-danger text-white">Habis / Ditolak</span>
```
**Sebelum commit**, cek: apakah teks badge terbaca jelas di atas
background-nya? Kalau ada badge yang mapping warnanya salah (mis. status
"good" tapi warna abu-abu tanpa label / warna sama dengan background), itu
harus diperbaiki, bukan dibiarkan.

### Form Filter
```html
<select class="form-select">...</select>
<button class="btn btn-primary">Filter</button>
```

### Tombol Aksi
- Primary/CTA utama (Simpan, Bayar, Tambah): `btn btn-primary`
- Aksi sekunder (Edit): `btn btn-outline-primary` atau `btn-outline-secondary`
- Aksi destruktif (Hapus): `btn btn-outline-danger` atau `btn-danger`
- **Jangan** ada tombol CTA utama yang warnanya pudar/abu-abu low-contrast —
  itu membingungkan user soal mana aksi yang penting.

### Icon
Gunakan Tabler Icons (`<i class="ti ti-[nama-icon]"></i>`), konsisten di
seluruh sidebar dan tombol aksi. Jangan campur dengan icon set lain
(Font Awesome, Bootstrap Icons, dll) di aplikasi yang sama.

## 4. Checklist Sebelum Menganggap Halaman "Selesai"

Setiap kali membuat/mengedit halaman, jawab checklist ini sebelum lanjut:

- [ ] Layout pakai struktur `page` > `navbar-vertical` + `page-wrapper` >
      `page-header` + `page-body` seperti di §2?
- [ ] Semua "kotak info" pakai `.card`, bukan div custom?
- [ ] Semua tabel pakai `.table.card-table.table-vcenter` dalam
      `.table-responsive` dalam `.card`?
- [ ] Semua badge status kontras dan terbaca?
- [ ] Tombol CTA utama pakai `btn-primary` solid?
- [ ] Tidak ada whitespace kosong besar yang tidak wajar di layar lebar
      (grid/card sudah fill dengan kolom yang proporsional)?
- [ ] Class yang dipakai memang ada di Tabler (bukan karangan) — kalau
      ragu, cek demo resmi dulu?
- [ ] Konsisten dengan halaman lain yang sejenis di aplikasi ini (list page
      mirip list page, form page mirip form page)?

## 5. Batasan

- Jangan mengubah struktur data/controller/route Laravel hanya untuk
  keperluan styling — perbaikan UI harus terbatas di Blade template + CSS.
- Jangan install ulang/downgrade/upgrade versi Tabler tanpa konfirmasi user.
- Kalau menemukan halaman yang menyimpang jauh dari standar ini (custom
  color hardcoded, struktur non-Tabler, dll), laporkan ke user dulu sebelum
  refactor besar, kecuali user memang sedang meminta refactor tersebut.
