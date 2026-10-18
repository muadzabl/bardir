# BARKAD OS (Barbershop Operation & Digital System) 💈

Sistem Operasional, Kasir (POS), Antrean, dan Penggajian Komisi Barbershop Modern berbasis Web.

> ⚠️ **[Revisi v2.0 — 2026-10-18]** Backend bermigrasi dari PHP Native ke **Laravel 11.x** sesuai catatan dosen penguji. Penambahan modul Kapster (F-KAP-01, F-KAP-02) dan Pelanggan (F-CUS-01, F-CUS-02, F-CUS-03).

---

## 🌟 Fitur Utama Sistem

1. **Sistem Login & Hak Akses (Role-Based Access Control):**
   - **Owner:** Kendali penuh — manajemen kapster, harga layanan, komisi, akun kasir, dan laporan omset.
   - **Kasir:** Pencatatan transaksi POS harian dan pemilihan metode pembayaran.
   - **Kapster:** ⭐ *[BARU]* Dashboard performa & komisi personal + monitor antrean job.
   - **Pelanggan:** ⭐ *[BARU]* Akses publik — katalog layanan, layar antrean real-time, struk digital.

2. **Manajemen Pegawai / Kapster (CRUD Lengkap):**
   - Tambah kapster baru (Nama, Nomor WhatsApp, Status).
   - Edit status keaktifan kapster (Aktif / Non-Aktif / Cuti).
   - Hapus / Arsipkan data kapster.

3. **Manajemen Layanan & Tarif Komisi Fleksibel (CRUD Lengkap):**
   - Tambah dan edit menu layanan potong rambut / perawatan.
   - Atur harga jual ke pelanggan dan tarif komisi hak kapster (Rp) secara dinamis.
   - Perhitungan otomatis bagi hasil: `Pemasukan Bersih Owner = Harga Layanan - Komisi Kapster`.

4. **Kasir POS & Fitur Diskon / Promo:**
   - Input transaksi multi-layanan (satu pelanggan, beberapa layanan/kapster).
   - Input potongan diskon (Rp) yang otomatis memotong total tagihan.
   - Pilihan metode bayar: Cash, QRIS, Transfer Bank, Kartu Debit.

5. **Automated Payroll & Live Revenue Tracker:**
   - Laporan perolehan komisi kapster berdasarkan filter rentang tanggal.
   - Rekap performa harian jumlah kepala yang dicukur per kapster.

6. **⭐ [BARU] Dashboard Performa & Komisi Kapster (F-KAP-01):**
   - Kapster melihat jumlah pelanggan yang dilayani dan akumulasi komisi pribadi secara real-time.

7. **⭐ [BARU] Monitor Antrean Kapster (F-KAP-02):**
   - Kapster melihat daftar antrean pelanggan yang dialokasikan kepadanya beserta statusnya.

8. **⭐ [BARU] Katalog Layanan Publik (F-CUS-01):**
   - Pelanggan melihat menu dan harga layanan secara transparan tanpa perlu login.

9. **⭐ [BARU] Layar Antrean Real-Time (F-CUS-02):**
   - Pelanggan memantau nomor antrean berjalan melalui layar tampilan publik.

10. **⭐ [BARU] Struk Pembayaran Digital (F-CUS-03):**
    - Pelanggan menerima bukti pembayaran (cetak fisik atau QR digital) setelah checkout.

---

## 🛠️ Tech Stack

| Layer | Teknologi | Keterangan |
|-------|-----------|------------|
| **Backend** | **Laravel 11.x** | ⚠️ REVISI dari PHP Native — MVC, Eloquent ORM, Sanctum Auth |
| Bahasa | PHP >= 8.2 | Persyaratan minimum Laravel 11.x |
| Database | MySQL 8.x | Laravel Migrations & Seeders |
| Template | Blade Engine | Bawaan Laravel |
| Styling | Bootstrap 5 + Vanilla CSS | — |
| JavaScript | Alpine.js / Vanilla JS | Real-time polling AJAX |
| Dev Server | Laragon (Windows) | — |

---

## 📂 Struktur Direktori Proyek (Laravel 11.x)

```text
BARDIR/
├── app/
│   ├── Http/Controllers/
│   │   ├── Auth/AuthController.php          # Login, Logout, Session
│   │   ├── OwnerController.php              # Dashboard & manajemen owner
│   │   ├── KasirController.php              # POS & transaksi kasir
│   │   ├── KapsterController.php            # [BARU] Dashboard & antrean kapster
│   │   ├── CustomerController.php           # [BARU] Katalog & display antrean
│   │   ├── BarberController.php             # CRUD data kapster
│   │   ├── ServiceController.php            # CRUD layanan & tarif
│   │   ├── TransactionController.php        # Checkout POS & struk
│   │   └── PayrollController.php            # Laporan komisi
│   ├── Models/
│   │   ├── User.php, Barber.php, Service.php
│   │   ├── Transaction.php, TransactionItem.php
│   │   └── Queue.php                        # [BARU] Model antrean pelanggan
│   └── Services/
│       ├── PayrollService.php
│       ├── QueueService.php                 # [BARU] Logika antrean
│       └── ReceiptService.php               # [BARU] Generasi struk digital
├── database/migrations/                     # Laravel Migrations (bukan DDL manual)
├── resources/views/
│   ├── owner/, kasir/                       # View per peran
│   ├── kapster/                             # [BARU] dashboard.blade.php, queue.blade.php
│   └── customer/                            # [BARU] catalog.blade.php, display.blade.php, receipt.blade.php
├── routes/web.php                           # Semua route dengan RBAC middleware
├── docs/
│   ├── requirements.md                      # Kebutuhan fungsional & tech stack
│   ├── SRS.md                               # Software Requirements Specification
│   └── arsitektur.md                        # Arsitektur sistem & ERD
└── README.md
```

---

## 📚 Dokumentasi Proyek

| Dokumen | Deskripsi |
|---------|-----------|
| [requirements.md](docs/requirements.md) | Kebutuhan fungsional per peran & tech stack |
| [SRS.md](docs/SRS.md) | Spesifikasi kebutuhan perangkat lunak (IEEE 830) |
| [arsitektur.md](docs/arsitektur.md) | Arsitektur MVC, ERD, routing, & alur data |

---

## 🔐 Akses Web & Akun Login (Laravel 11.x)

Buka di browser:
👉 **[http://localhost/BARKAD/public/](http://localhost/BARKAD/public/)** *(atau via `php artisan serve` di `http://127.0.0.1:8000`)*

Halaman login telah dilengkapi tombol **1-Klik Demo**:

| Role | Email | Password | Hak Akses |
|---|---|---|---|
| **Owner** | `owner@barberos.local` | `password123` | Akses Penuh: Ubah Harga, Komisi, Kapster, Stok, Laporan Omset |
| **Kasir** | `cashier@barberos.local` | `password123` | Akses POS Kasir, Cetak Struk, Antrean Pelanggan |
| **Kapster** | `rian@barberos.local` / `doni@barberos.local` / `eko@barberos.local` | `password123` | Dashboard Komisi Personal (F-KAP-01) & Antrean Job (F-KAP-02) |
| **Pelanggan** | *(Tanpa Login)* | — | Ambil Nomor Antrean Mandiri (F-CUS-01), Live Tracker (F-CUS-02), Katalog (F-CUS-03) |

