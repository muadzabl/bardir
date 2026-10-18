# 🏗️ BARKAD OS — Dokumen Arsitektur Sistem
**Proyek:** BARKAD OS (Barbershop Operation & Digital System)  
**Versi:** 2.0  
**Tanggal Revisi:** 2026-10-18  
**Status:** Direvisi berdasarkan catatan dosen penguji — Migrasi ke Laravel 11.x

---

## 1. Gambaran Arsitektur

### 1.1 Pola Arsitektur
BARKAD OS menggunakan pola arsitektur **MVC (Model-View-Controller)** bawaan Laravel 11.x.

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                         │
│  Browser (Owner/Kasir/Kapster) │ Display Screen (Pelanggan) │
└───────────────────┬─────────────────────────────────────────┘
                    │ HTTP Request (CSRF Protected)
┌───────────────────▼─────────────────────────────────────────┐
│                     LARAVEL 11.x (MVC)                      │
│                                                             │
│  ┌──────────┐    ┌──────────────┐    ┌───────────────────┐  │
│  │  Routes  │───▶│  Controller  │───▶│  Service / Model  │  │
│  │ web.php  │    │  (RBAC Gate) │    │  (Eloquent ORM)   │  │
│  └──────────┘    └──────┬───────┘    └─────────┬─────────┘  │
│                         │                      │             │
│                  ┌──────▼───────┐    ┌─────────▼─────────┐  │
│                  │  Blade View  │    │   MySQL Database   │  │
│                  │  (Template)  │    │   (migrations)     │  │
│                  └──────────────┘    └───────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### 1.2 Alasan Migrasi ke Laravel 11.x
> **[REVISI T-BE-01]** Backend sebelumnya menggunakan PHP Native dengan file API terpisah (`/api/*.php`). Revisi dosen penguji mengharuskan migrasi ke **Laravel 11.x** dengan justifikasi:
> - Arsitektur **MVC bawaan** yang terstruktur dan maintainable
> - **Eloquent ORM** menggantikan query PDO manual
> - **Session Auth + CSRF Protection** bawaan untuk keamanan
> - **Laravel Sanctum** tersedia untuk ekspansi API di masa mendatang

---

## 2. Struktur Direktori Proyek (Laravel 11.x)

```
BARDIR/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── AuthController.php       # Login, Logout, Session
│   │   │   ├── OwnerController.php          # Dashboard & manajemen owner
│   │   │   ├── KasirController.php          # POS & transaksi kasir
│   │   │   ├── KapsterController.php        # [BARU] Dashboard & antrean kapster
│   │   │   ├── CustomerController.php       # [BARU] Katalog & display antrean
│   │   │   ├── BarberController.php         # CRUD data kapster
│   │   │   ├── ServiceController.php        # CRUD layanan & tarif
│   │   │   ├── TransactionController.php    # Checkout POS & struk
│   │   │   └── PayrollController.php        # Laporan komisi
│   │   ├── Middleware/
│   │   │   ├── RoleMiddleware.php           # RBAC: owner/kasir/kapster
│   │   │   └── VerifyCsrfToken.php          # (bawaan Laravel)
│   │   └── Requests/
│   │       ├── TransactionRequest.php       # Validasi form transaksi
│   │       └── BarberRequest.php            # Validasi form kapster
│   ├── Models/
│   │   ├── User.php                         # Model user (owner/kasir/kapster)
│   │   ├── Barber.php                       # Model kapster
│   │   ├── Service.php                      # Model layanan & tarif
│   │   ├── Transaction.php                  # Model transaksi POS
│   │   ├── TransactionItem.php              # Model item dalam transaksi
│   │   └── Queue.php                        # [BARU] Model antrean pelanggan
│   └── Services/
│       ├── PayrollService.php               # Logika perhitungan komisi
│       ├── QueueService.php                 # [BARU] Logika manajemen antrean
│       └── ReceiptService.php               # [BARU] Generasi struk digital
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_create_users_table.php
│   │   ├── 2024_01_02_create_barbers_table.php
│   │   ├── 2024_01_03_create_services_table.php
│   │   ├── 2024_01_04_create_transactions_table.php
│   │   ├── 2024_01_05_create_transaction_items_table.php
│   │   └── 2024_01_06_create_queues_table.php     # [BARU]
│   └── seeders/
│       └── DatabaseSeeder.php
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   └── app.blade.php                # Layout utama (sidebar RBAC)
│   │   ├── auth/
│   │   │   └── login.blade.php
│   │   ├── owner/
│   │   │   ├── dashboard.blade.php
│   │   │   ├── barbers.blade.php
│   │   │   ├── services.blade.php
│   │   │   └── payroll.blade.php
│   │   ├── kasir/
│   │   │   ├── pos.blade.php
│   │   │   └── transactions.blade.php
│   │   ├── kapster/                          # [BARU]
│   │   │   ├── dashboard.blade.php           # F-KAP-01: Komisi & performa
│   │   │   └── queue.blade.php               # F-KAP-02: Antrean personal
│   │   └── customer/                         # [BARU]
│   │       ├── catalog.blade.php             # F-CUS-01: Katalog layanan publik
│   │       ├── display.blade.php             # F-CUS-02: Monitor antrean real-time
│   │       └── receipt.blade.php             # F-CUS-03: Struk pembayaran digital
│   └── css/
│       └── app.css
├── routes/
│   ├── web.php                              # Definisi semua route
│   └── api.php                              # (Opsional: API untuk AJAX)
├── config/
│   ├── auth.php
│   └── app.php
├── .env                                     # Konfigurasi database & app
├── composer.json
└── README.md
```

---

## 3. Arsitektur Database (ERD — Skema Relasional)

### 3.1 Tabel Utama

```
┌─────────────┐     ┌──────────────────┐     ┌──────────────┐
│    users    │     │   transactions   │     │   barbers    │
├─────────────┤     ├──────────────────┤     ├──────────────┤
│ id          │──┐  │ id               │  ┌──│ id           │
│ name        │  │  │ user_id (FK)     │◀─┘  │ name         │
│ email       │  └─▶│ barber_id (FK)   │◀────│ phone_wa     │
│ password    │     │ total_price      │     │ status       │
│ role        │     │ discount         │     │ created_at   │
│ created_at  │     │ payment_method   │     └──────────────┘
└─────────────┘     │ created_at       │
                    └────────┬─────────┘
                             │ 1:N
                    ┌────────▼─────────┐     ┌──────────────┐
                    │ transaction_items│     │   services   │
                    ├──────────────────┤     ├──────────────┤
                    │ id               │  ┌──│ id           │
                    │ transaction_id   │  │  │ name         │
                    │ service_id (FK)  │◀─┘  │ price        │
                    │ barber_id (FK)   │     │ commission   │
                    │ price            │     │ created_at   │
                    │ commission       │     └──────────────┘
                    └──────────────────┘

                    ┌──────────────────┐     [BARU]
                    │      queues      │
                    ├──────────────────┤
                    │ id               │
                    │ queue_number     │
                    │ barber_id (FK)   │
                    │ service_id (FK)  │
                    │ status           │ ← waiting/serving/done
                    │ created_at       │
                    └──────────────────┘
```

### 3.2 Penjelasan Tabel `queues` (Baru)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT UNSIGNED (PK) | Auto-increment |
| queue_number | SMALLINT | Nomor antrean harian (reset tiap hari) |
| barber_id | BIGINT FK → barbers.id | Kapster yang ditugaskan |
| service_id | BIGINT FK → services.id | Layanan yang dipilih |
| status | ENUM('waiting','serving','done') | Status antrean saat ini |
| customer_name | VARCHAR(100) NULLABLE | Nama/inisial pelanggan (opsional) |
| created_at | TIMESTAMP | Waktu masuk antrean |
| updated_at | TIMESTAMP | Waktu pembaruan status |

---

## 4. Arsitektur Routing (RBAC)

```
routes/web.php

# Public Routes (tanpa autentikasi) — Untuk Pelanggan
GET  /catalog              → CustomerController@catalog     [F-CUS-01]
GET  /display              → CustomerController@display     [F-CUS-02]
GET  /receipt/{id}         → CustomerController@receipt     [F-CUS-03]

# Auth Routes
GET  /login                → AuthController@showLogin
POST /login                → AuthController@login  (CSRF protected)
POST /logout               → AuthController@logout

# Owner Routes (middleware: auth, role:owner)
GET  /owner/dashboard      → OwnerController@dashboard      [F-OWN-01]
GET  /owner/barbers        → BarberController@index         [F-OWN-02]
POST /owner/barbers        → BarberController@store
PUT  /owner/barbers/{id}   → BarberController@update
DELETE /owner/barbers/{id} → BarberController@destroy
GET  /owner/services       → ServiceController@index        [F-OWN-03]
GET  /owner/payroll        → PayrollController@index        [F-OWN-05]

# Kasir Routes (middleware: auth, role:kasir)
GET  /kasir/pos            → KasirController@pos            [F-KAS-02]
POST /kasir/checkout       → TransactionController@store    [F-KAS-05]
GET  /kasir/transactions   → TransactionController@index    [F-KAS-06]

# Kapster Routes — [BARU] (middleware: auth, role:kapster)
GET  /kapster/dashboard    → KapsterController@dashboard    [F-KAP-01]
GET  /kapster/queue        → KapsterController@queue        [F-KAP-02]
PUT  /kapster/queue/{id}   → KapsterController@updateStatus [F-KAP-02]
```

---

## 5. Alur Data Antrean (Flow Diagram)

```
Pelanggan Datang
       │
       ▼
[Kasir membuka antrean baru]
       │  POST /kasir/queue/create
       ▼
[queues table: status='waiting']
       │
       ├──── [Display Publik /display] ──▶ Pelanggan lihat nomor antrean [F-CUS-02]
       │
       ▼
[Kapster terima notifikasi di /kapster/queue] [F-KAP-02]
       │
       │  PUT /kapster/queue/{id} → status='serving'
       ▼
[Kapster melayani pelanggan]
       │
       ▼
[Kasir input transaksi di POS] [F-KAS-02]
       │  POST /kasir/checkout
       ▼
[transactions + transaction_items tersimpan]
       │
       ├──── [Struk dicetak/QR dikirim] ──▶ Pelanggan terima struk [F-CUS-03]
       │
       │  PUT /kapster/queue/{id} → status='done'
       ▼
[Dashboard Kapster update komisi & performa] [F-KAP-01]
```

---

## 6. Komponen Keamanan (Laravel Security Layer)

| Komponen | Implementasi Laravel | Tujuan |
|----------|---------------------|--------|
| CSRF Protection | Middleware `VerifyCsrfToken` + `@csrf` di Blade | Mencegah serangan CSRF |
| Autentikasi | `Auth::attempt()`, Session Guard | Login/logout berbasis session |
| Otorisasi RBAC | `Gate::define()` / Policy / Middleware `role:` | Kontrol akses per peran |
| Password Hashing | `Hash::make()` (bcrypt) | Menyimpan password aman |
| Query Injection | Eloquent ORM (parameterized queries) | Mencegah SQL Injection |
| Rate Limiting | `throttle` middleware pada login route | Mencegah brute force |

---

## 7. Stack Teknologi (Ringkasan)

| Layer | Teknologi | Versi |
|-------|-----------|-------|
| Backend Framework | **Laravel** | **11.x** ← REVISI |
| Bahasa | PHP | >= 8.2 |
| ORM | Eloquent ORM | (bundled Laravel) |
| Autentikasi | Laravel Session Auth + Sanctum | (bundled Laravel) |
| Template Engine | Blade | (bundled Laravel) |
| Styling | Bootstrap | 5.x |
| JavaScript | Alpine.js / Vanilla JS | 3.x |
| Real-time | AJAX Polling / Laravel Echo | — |
| Database | MySQL | 8.x |
| Migration | Laravel Migrations | (bundled Laravel) |
| Dev Server | Laragon | Windows |

---

## 8. Riwayat Revisi Arsitektur

| Versi | Tanggal | Perubahan |
|-------|---------|-----------|
| 1.0 | — | Arsitektur awal: PHP Native, file API terpisah (`/api/*.php`), PDO manual |
| 2.0 | 2026-10-18 | **Revisi dosen penguji:** (1) Migrasi backend ke Laravel 11.x MVC [T-BE-01] — menggantikan PHP Native sepenuhnya; (2) Penambahan modul Kapster (KapsterController, views kapster/, tabel queues) untuk F-KAP-01 & F-KAP-02; (3) Penambahan modul Customer publik (CustomerController, views customer/) untuk F-CUS-01, F-CUS-02, F-CUS-03 |
