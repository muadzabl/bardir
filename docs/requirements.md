# 📋 BARKAD OS — Requirements Document
**Proyek:** BARKAD OS (Barbershop Operation & Digital System)  
**Versi:** 2.0  
**Tanggal Revisi:** 2026-10-18  
**Status:** Direvisi berdasarkan catatan dosen penguji

---

## 1. Pendahuluan

### 1.1 Tujuan Dokumen
Dokumen ini mendefinisikan seluruh kebutuhan teknis dan fungsional sistem BARKAD OS — sebuah platform manajemen operasional barbershop modern berbasis web yang mencakup POS Kasir, manajemen antrean, penggajian komisi kapster, serta transparansi layanan kepada pelanggan.

### 1.2 Ruang Lingkup Sistem
BARKAD OS melayani empat peran pengguna:
- **Owner** — Pemilik barbershop dengan kendali penuh sistem
- **Kasir** — Operator transaksi harian
- **Kapster (Barber)** — Tukang cukur dengan akses personal dashboard
- **Pelanggan (Customer)** — Pengunjung / klien barbershop

---

## 2. Tech Stack

### 2.1 Backend

| Kode | Komponen | Spesifikasi | Justifikasi |
|------|----------|-------------|-------------|
| **T-BE-01** | Backend Framework | **Laravel 11.x** (PHP Framework) | ⚠️ **REVISI:** Bermigrasi dari PHP Native. Menggunakan arsitektur MVC bawaan Laravel, Eloquent ORM, serta pustaka keamanan bawaan (Sanctum/Session Auth & CSRF Protection) untuk mempercepat pengembangan dan menjaga pemeliharaan kode aplikasi. |
| T-BE-02 | Bahasa Pemrograman | PHP >= 8.2 | Persyaratan minimum Laravel 11.x |
| T-BE-03 | ORM | Eloquent ORM (built-in Laravel) | Menggantikan query PDO manual |
| T-BE-04 | Autentikasi | Laravel Sanctum / Session Auth | CSRF protection dan session management bawaan |
| T-BE-05 | Database Driver | MySQL 8.x via Laravel DB Facade | Konsistensi dengan skema existing |

### 2.2 Frontend

| Kode | Komponen | Spesifikasi |
|------|----------|-------------|
| T-FE-01 | UI Framework | Blade Template Engine (Laravel) |
| T-FE-02 | Styling | Vanilla CSS + Bootstrap 5 |
| T-FE-03 | JavaScript | Vanilla JS / Alpine.js |
| T-FE-04 | Real-time | Laravel Echo + Pusher / Polling AJAX |

### 2.3 Database & Infrastruktur

| Kode | Komponen | Spesifikasi |
|------|----------|-------------|
| T-DB-01 | Database | MySQL 8.x |
| T-DB-02 | Migration | Laravel Migrations & Seeders |
| T-INF-01 | Server (Dev) | Laragon (Windows) |
| T-INF-02 | Server (Prod) | VPS / Shared Hosting (Apache/Nginx) |

---

## 3. Kebutuhan Fungsional

### 3.1 Peran: Owner (Pemilik)

| Kode | Fitur | Deskripsi |
|------|-------|-----------|
| F-OWN-01 | Dashboard Ringkasan | Melihat omset harian/bulanan, jumlah transaksi, dan komisi terbayar secara real-time. |
| F-OWN-02 | Manajemen Kapster | CRUD data kapster (nama, nomor WhatsApp, status aktif/non-aktif/cuti). |
| F-OWN-03 | Manajemen Layanan & Tarif | CRUD menu layanan, harga jual, dan komisi kapster (Rp) secara dinamis. |
| F-OWN-04 | Manajemen Akun Kasir | Membuat, mengedit, dan menonaktifkan akun kasir. |
| F-OWN-05 | Laporan Payroll Komisi | Melihat laporan komisi kapster berdasarkan filter rentang tanggal. |
| F-OWN-06 | Pengaturan Komisi Global | Mengatur persentase atau nominal komisi default per jenis layanan. |

### 3.2 Peran: Kasir

| Kode | Fitur | Deskripsi |
|------|-------|-----------|
| F-KAS-01 | Login & Autentikasi | Masuk sistem menggunakan email dan password (Session Auth + CSRF). |
| F-KAS-02 | Input Transaksi POS | Mencatat transaksi multi-layanan (satu pelanggan, beberapa layanan/kapster). |
| F-KAS-03 | Diskon & Promo | Input potongan diskon (Rp) yang otomatis memotong total tagihan. |
| F-KAS-04 | Metode Pembayaran | Memilih metode bayar: Cash, QRIS, Transfer Bank, Kartu Debit. |
| F-KAS-05 | Checkout & Struk | Menyelesaikan transaksi dan mencetak/mengirim struk pembayaran. |
| F-KAS-06 | Riwayat Transaksi | Melihat riwayat transaksi harian yang telah dicatat. |

### 3.3 Peran: Kapster (Tukang Cukur) [BARU - Revisi Dosen Penguji]

| Kode | Fitur | Deskripsi |
|------|-------|-----------|
| F-KAP-01 | Dashboard Performa & Komisi Personal | Kapster dapat melihat jumlah pelanggan yang telah dilayani serta estimasi akumulasi komisi pribadi secara real-time. |
| F-KAP-02 | Status Antrean & Presensi Job | Kapster dapat melihat daftar antrean pelanggan yang ditujukan padanya, beserta status penanganan (menunggu / sedang dilayani / selesai). |

### 3.4 Peran: Pelanggan (Customer) [BARU - Revisi Dosen Penguji]

| Kode | Fitur | Deskripsi |
|------|-------|-----------|
| F-CUS-01 | Katalog Layanan & Tarif | Pelanggan dapat melihat daftar menu pemotongan rambut, perawatan, dan informasi harga secara transparan tanpa perlu login. |
| F-CUS-02 | Monitor Antrean Real-Time | Pelanggan dapat memantau status nomor antrean berjalan melalui layar tampilan publik (display screen). |
| F-CUS-03 | Penerimaan Struk Pembayaran | Pelanggan menerima bukti pembayaran/struk transaksi (cetak fisik atau digital/QR) setelah proses checkout selesai. |

---

## 4. Kebutuhan Non-Fungsional

| Kode | Kategori | Kebutuhan |
|------|----------|-----------|
| NF-01 | Keamanan | CSRF protection wajib aktif pada seluruh form (bawaan Laravel). |
| NF-02 | Keamanan | Password di-hash menggunakan `bcrypt` (Laravel Hash facade). |
| NF-03 | Performa | Halaman dashboard harus load < 3 detik pada koneksi lokal. |
| NF-04 | Maintainability | Kode mengikuti standar PSR-12 dan konvensi penamaan Laravel. |
| NF-05 | Skalabilitas | Arsitektur MVC memungkinkan penambahan modul tanpa mengubah core. |
| NF-06 | Kompatibilitas | Antarmuka responsif, mendukung browser modern (Chrome, Firefox, Edge). |
| NF-07 | Ketersediaan | Sistem tersedia 24/7 selama jam operasional barbershop. |

---

## 5. Batasan Sistem

- Sistem dirancang untuk satu outlet barbershop (single-tenant).
- Tidak mencakup fitur e-commerce atau pemesanan online (booking).
- Struk digital via QR bergantung pada integrasi layanan email/WhatsApp.

---

## 6. Riwayat Revisi

| Versi | Tanggal | Perubahan | Oleh |
|-------|---------|-----------|------|
| 1.0 | — | Rilis awal (PHP Native) | Tim Pengembang |
| 2.0 | 2026-10-18 | Revisi dosen penguji: (1) Backend bermigrasi ke Laravel 11.x [T-BE-01], (2) Penambahan kebutuhan fungsional Kapster [F-KAP-01, F-KAP-02] dan Pelanggan [F-CUS-01, F-CUS-02, F-CUS-03] | Tim Pengembang |
