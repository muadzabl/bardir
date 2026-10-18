# 📄 BARKAD OS — Software Requirements Specification (SRS)
**Proyek:** BARKAD OS (Barbershop Operation & Digital System)  
**Versi Dokumen:** 2.0  
**Standar Referensi:** IEEE 830-1998 (adaptasi)  
**Tanggal Revisi:** 2026-10-18  
**Status:** Direvisi berdasarkan catatan dosen penguji

---

## 1. Pendahuluan

### 1.1 Tujuan
Dokumen SRS ini mendeskripsikan secara formal spesifikasi perangkat lunak BARKAD OS kepada seluruh pemangku kepentingan: pengembang, penguji, dan dosen pembimbing. Dokumen ini menjadi kontrak teknis yang mengikat antara tim pengembang dan kebutuhan sistem.

### 1.2 Lingkup Produk
**BARKAD OS** adalah sistem informasi manajemen barbershop berbasis web yang menyediakan:
- Point-of-Sale (POS) Kasir digital
- Manajemen komisi dan penggajian kapster
- Manajemen antrean pelanggan real-time
- Dashboard performa personal kapster
- Katalog layanan publik untuk pelanggan
- Laporan keuangan untuk owner

### 1.3 Definisi, Akronim, dan Singkatan

| Istilah | Definisi |
|---------|----------|
| BARKAD OS | Barbershop Operation & Digital System |
| POS | Point of Sale — sistem kasir digital |
| MVC | Model-View-Controller — pola arsitektur perangkat lunak |
| ORM | Object-Relational Mapping — abstraksi query database |
| CSRF | Cross-Site Request Forgery — jenis serangan web |
| Kapster | Tukang cukur / barber yang bekerja di barbershop |
| SRS | Software Requirements Specification |
| RBAC | Role-Based Access Control |

### 1.4 Referensi
- Laravel 11.x Official Documentation — https://laravel.com/docs/11.x
- IEEE Std 830-1998: Recommended Practice for SRS
- Catatan Revisi Dosen Penguji — 2026-10-18

### 1.5 Gambaran Umum Dokumen
Bagian 2 berisi gambaran umum sistem. Bagian 3 berisi spesifikasi kebutuhan fungsional per peran. Bagian 4 berisi kebutuhan non-fungsional. Bagian 5 berisi batasan desain dan asumsi.

---

## 2. Gambaran Umum Sistem

### 2.1 Perspektif Produk
BARKAD OS adalah sistem standalone berbasis web yang berjalan di atas stack Laravel 11.x. Sistem ini menggantikan pencatatan manual barbershop dan mengintegrasikan alur kerja Owner, Kasir, Kapster, dan Pelanggan dalam satu platform.

### 2.2 Fungsi Produk (Ringkasan)
```
BARKAD OS
├── Modul Owner       → Manajemen aset, laporan, pengaturan sistem
├── Modul Kasir       → POS transaksi, checkout, struk pembayaran
├── Modul Kapster     → Dashboard komisi personal, monitor antrean [BARU]
└── Modul Pelanggan   → Katalog layanan publik, monitor antrean [BARU]
```

### 2.3 Kelas & Karakteristik Pengguna

| Peran | Frekuensi Akses | Tingkat Teknis | Akses Sistem |
|-------|----------------|----------------|--------------|
| Owner | Harian / Mingguan | Menengah | Full access (semua modul) |
| Kasir | Setiap shift | Dasar | Modul POS & transaksi |
| Kapster | Setiap shift | Dasar | Dashboard personal & antrean |
| Pelanggan | Saat kunjungan | Awam | Publik (tanpa login) |

### 2.4 Lingkungan Operasional
- **Server Development:** Laragon (Windows), PHP 8.2+, MySQL 8.x
- **Server Production:** VPS/Shared Hosting dengan dukungan PHP 8.2+ dan MySQL
- **Browser:** Chrome 90+, Firefox 90+, Microsoft Edge 90+
- **Resolusi Display:** Minimum 1280×720 (desktop), 768×1024 (tablet)

### 2.5 Asumsi dan Ketergantungan
- Instalasi Composer tersedia untuk manajemen paket Laravel.
- Koneksi internet dibutuhkan hanya untuk fitur notifikasi WhatsApp (opsional).
- Satu instance sistem untuk satu outlet barbershop (single-tenant).

---

## 3. Spesifikasi Kebutuhan Fungsional

### 3.1 USE CASE: Peran Owner

#### UC-OWN-01: Login Owner
- **Aktor:** Owner
- **Prasyarat:** Akun owner telah terdaftar di database
- **Alur Utama:**
  1. Owner membuka halaman login
  2. Owner memasukkan email dan password
  3. Laravel memvalidasi CSRF token dan kredensial via Session Auth
  4. Sistem mengarahkan ke Dashboard Owner
- **Alur Alternatif:** Jika kredensial salah, tampilkan pesan error
- **Kebutuhan Terkait:** T-BE-01 (Laravel), T-BE-04 (Auth)

#### UC-OWN-02: Manajemen Kapster (CRUD)
- **Aktor:** Owner
- **Deskripsi:** Owner mengelola data kapster yang bekerja di barbershop
- **Data yang Dikelola:** Nama, Nomor WhatsApp, Status (Aktif/Non-Aktif/Cuti)
- **Validasi:** Nama wajib diisi, nomor WhatsApp format Indonesia

#### UC-OWN-03: Manajemen Layanan & Tarif
- **Aktor:** Owner
- **Deskripsi:** Owner mengelola katalog layanan beserta harga dan komisi kapster
- **Data yang Dikelola:** Nama layanan, harga jual (Rp), komisi kapster (Rp/%)
- **Aturan Bisnis:** `Pendapatan Bersih Owner = Harga Layanan - Komisi Kapster`

---

### 3.2 USE CASE: Peran Kasir

#### UC-KAS-01: Input Transaksi POS
- **Aktor:** Kasir
- **Prasyarat:** Kasir telah login; terdapat pelanggan aktif di kursi
- **Alur Utama:**
  1. Kasir membuka modul POS
  2. Kasir memilih kapster yang melayani
  3. Kasir memilih satu atau beberapa layanan
  4. Sistem menghitung subtotal dan komisi secara otomatis
  5. Kasir menerapkan diskon (opsional)
  6. Kasir memilih metode pembayaran
  7. Kasir menekan "Checkout" — sistem menyimpan transaksi dan mencetak struk
- **Alur Alternatif:** Jika metode pembayaran belum dipilih, tampilkan validasi

#### UC-KAS-02: Cetak/Kirim Struk Pembayaran
- **Aktor:** Kasir, Pelanggan (penerima)
- **Deskripsi:** Setelah checkout, sistem menghasilkan struk yang dapat dicetak fisik atau ditampilkan sebagai QR digital
- **Kebutuhan Terkait:** F-CUS-03 (penerimaan struk oleh pelanggan)

---

### 3.3 USE CASE: Peran Kapster (Tukang Cukur) [BARU]

#### UC-KAP-01: Akses Dashboard Performa & Komisi Personal
- **Kode Fungsional:** F-KAP-01
- **Aktor:** Kapster
- **Prasyarat:** Kapster telah login dengan akun personal
- **Alur Utama:**
  1. Kapster membuka dashboard personal
  2. Sistem menampilkan jumlah pelanggan yang telah dilayani (hari ini / bulan ini)
  3. Sistem menghitung dan menampilkan estimasi akumulasi komisi pribadi secara real-time
  4. Data diperbarui otomatis setiap kali kasir melakukan checkout yang melibatkan kapster tersebut
- **Output:** Widget ringkasan: "X Pelanggan Dilayani | Rp Y,- Komisi Terkumpul"
- **Kebutuhan Non-Fungsional Terkait:** NF-03 (performa), NF-07 (ketersediaan)

#### UC-KAP-02: Monitor Antrean & Status Presensi Job
- **Kode Fungsional:** F-KAP-02
- **Aktor:** Kapster
- **Prasyarat:** Kapster sedang dalam status shift aktif
- **Alur Utama:**
  1. Kapster membuka halaman antrean personal
  2. Sistem menampilkan daftar pelanggan dalam antrean yang dialokasikan kepada kapster ini
  3. Setiap item antrean menampilkan: nomor antrean, nama/inisial pelanggan, layanan yang dipilih, status (Menunggu / Sedang Dilayani / Selesai)
  4. Kapster dapat memperbarui status antrean (tandai "Sedang Dilayani" atau "Selesai")
- **Output:** Tampilan daftar antrean real-time dengan status badge berwarna

---

### 3.4 USE CASE: Peran Pelanggan (Customer) [BARU]

#### UC-CUS-01: Lihat Katalog Layanan & Tarif
- **Kode Fungsional:** F-CUS-01
- **Aktor:** Pelanggan (anonim, tanpa login)
- **Prasyarat:** Pelanggan mengakses URL publik sistem
- **Alur Utama:**
  1. Pelanggan membuka halaman katalog publik
  2. Sistem menampilkan daftar semua layanan aktif
  3. Setiap layanan menampilkan: nama layanan, deskripsi singkat, dan harga (Rp)
  4. Harga bersifat transparan dan tidak memerlukan autentikasi
- **Output:** Halaman katalog yang bisa diakses via tablet/layar di area tunggu barbershop

#### UC-CUS-02: Monitor Antrean Real-Time
- **Kode Fungsional:** F-CUS-02
- **Aktor:** Pelanggan (anonim)
- **Prasyarat:** Sistem antrean aktif
- **Alur Utama:**
  1. Pelanggan melihat layar tampilan antrean (display screen publik)
  2. Sistem menampilkan: nomor antrean sedang dilayani, estimasi waktu tunggu, nama/inisial kapster yang bertugas
  3. Data diperbarui secara real-time (polling AJAX atau WebSocket)
- **Output:** Tampilan layar besar yang dirancang untuk dipasang di area tunggu barbershop

#### UC-CUS-03: Penerimaan Struk Pembayaran
- **Kode Fungsional:** F-CUS-03
- **Aktor:** Pelanggan, Kasir (pemicu)
- **Prasyarat:** Transaksi telah selesai di-checkout oleh kasir
- **Alur Utama:**
  1. Kasir menyelesaikan proses checkout
  2. Sistem menghasilkan struk transaksi
  3. **Opsi A (Fisik):** Printer thermal mencetak struk kertas
  4. **Opsi B (Digital):** Sistem menampilkan QR code yang berisi ringkasan transaksi (dapat discan oleh pelanggan)
- **Data pada Struk:** Nomor transaksi, tanggal/jam, nama kapster, daftar layanan + harga, diskon, total, metode bayar
- **Kebutuhan Terkait:** UC-KAS-02

---

## 4. Kebutuhan Non-Fungsional

### 4.1 Keamanan (Security)
| ID | Kebutuhan |
|----|-----------|
| NF-SEC-01 | Semua form menggunakan CSRF token dari Laravel (middleware `VerifyCsrfToken`). |
| NF-SEC-02 | Password di-hash menggunakan `bcrypt` melalui `Hash::make()`. |
| NF-SEC-03 | Akses endpoint API dilindungi middleware `auth` berbasis session. |
| NF-SEC-04 | Endpoint publik (katalog, antrean display) tidak memerlukan autentikasi namun dibatasi metode HTTP. |
| NF-SEC-05 | Data sensitif (komisi, omset) hanya dapat diakses oleh peran Owner. |

### 4.2 Performa (Performance)
| ID | Kebutuhan |
|----|-----------|
| NF-PERF-01 | Waktu muat halaman dashboard < 3 detik pada koneksi LAN lokal. |
| NF-PERF-02 | Pembaruan data antrean real-time dengan interval polling maksimal 5 detik. |
| NF-PERF-03 | Query database dioptimalkan menggunakan Eloquent eager loading (`with()`). |

### 4.3 Keterpeliharaan (Maintainability)
| ID | Kebutuhan |
|----|-----------|
| NF-MAINT-01 | Kode mengikuti standar PSR-12 dan konvensi penamaan Laravel (snake_case untuk DB, PascalCase untuk Model). |
| NF-MAINT-02 | Setiap perubahan skema database menggunakan Laravel Migration (tidak boleh DDL manual). |
| NF-MAINT-03 | Business logic ditempatkan di Service Class / Model, bukan di Controller. |

### 4.4 Skalabilitas (Scalability)
| ID | Kebutuhan |
|----|-----------|
| NF-SCALE-01 | Arsitektur MVC Laravel memungkinkan penambahan modul baru tanpa mengubah core. |
| NF-SCALE-02 | Role baru dapat ditambahkan melalui middleware dan Gate/Policy Laravel. |

---

## 5. Batasan Desain

### 5.1 Teknologi yang Diwajibkan
- **Backend:** Laravel 11.x (tidak boleh PHP Native — sesuai revisi T-BE-01)
- **Database:** MySQL 8.x
- **PHP:** Versi minimum 8.2

### 5.2 Batasan Bisnis
- Sistem single-tenant (satu barbershop per instalasi)
- Tidak mencakup sistem booking/reservasi online
- Tidak mencakup manajemen inventori produk perawatan

---

## 6. Traceability Matrix

| Kebutuhan Fungsional | Use Case | Tech Stack Terkait |
|----------------------|----------|--------------------|
| F-OWN-01 s/d F-OWN-06 | UC-OWN-01 s/d UC-OWN-03 | T-BE-01, T-BE-03, T-BE-04 |
| F-KAS-01 s/d F-KAS-06 | UC-KAS-01 s/d UC-KAS-02 | T-BE-01, T-BE-04, T-FE-01 |
| F-KAP-01 | UC-KAP-01 | T-BE-01, T-BE-03, T-FE-04 |
| F-KAP-02 | UC-KAP-02 | T-BE-01, T-FE-03, T-FE-04 |
| F-CUS-01 | UC-CUS-01 | T-BE-01, T-FE-01, T-FE-02 |
| F-CUS-02 | UC-CUS-02 | T-FE-03, T-FE-04 |
| F-CUS-03 | UC-CUS-03 | T-BE-01, T-FE-01 |

---

## 7. Riwayat Revisi

| Versi | Tanggal | Perubahan |
|-------|---------|-----------|
| 1.0 | — | Draft awal SRS (PHP Native) |
| 2.0 | 2026-10-18 | Revisi dosen penguji: T-BE-01 bermigrasi ke Laravel 11.x; Penambahan UC-KAP-01, UC-KAP-02, UC-CUS-01, UC-CUS-02, UC-CUS-03 beserta kebutuhan fungsional F-KAP-01, F-KAP-02, F-CUS-01, F-CUS-02, F-CUS-03 |
