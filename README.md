# BARDIR (Barbershop Operation & Payroll System) 💈

Sistem Operasional, Kasir (POS), dan Penggajian Komisi Barbershop Modern berbasis Web.

---

## 🌟 Fitur Utama Sistem

1. **Sistem Login & Hak Akses (Role-Based Access Control):**
   - **Owner:** Memiliki kendali penuh untuk menambah/mengedit pegawai kapster, mengubah harga layanan, mengatur besaran komisi, melihat keuntungan bersih owner, dan membuat akun kasir.
   - **Kasir:** Fokus pada pencatatan transaksi masuk harian dan pemilihan metode pembayaran (Tunai, QRIS, Transfer, Debit).

2. **Manajemen Pegawai / Kapster (CRUD Lengkap):**
   - Tambah kapster baru (Nama, Nomor WhatsApp, Status).
   - Edit status keaktifan kapster (Aktif / Non-Aktif / Cuti).
   - Hapus / Arsipkan data kapster.

3. **Manajemen Layanan & Tarif Komisi Fleksibel (CRUD Lengkap):**
   - Tambah dan edit menu layanan potong rambut / perawatan.
   - Atur harga jual ke pelanggan dan tarif komisi hak kapster (Rp) secara dinamis.
   - Perhitungan otomatis bagi hasil: `Pemasukan Bersih Owner = Harga Layanan - Komisi Kapster`.

4. **Kasir POS & Fitur Diskon / Promo:**
   - Input transaksi multi-layanan (satu pelanggan mengambil lebih dari 1 layanan dengan kapster berbeda/sama).
   - Input potongan diskon (Rp) yang otomatis memotong total tagihan.
   - Pilihan metode bayar: Cash, QRIS, Transfer Bank, Kartu Debit.

5. **Automated Payroll & Live Revenue Tracker:**
   - Laporan perolehan komisi kapster berdasarkan filter rentang tanggal.
   - Rekap performa harian jumlah kepala yang dicukur per kapster.

---

## 📂 Struktur Direktori Proyek

```text
BARDIR/
├── api/
│   ├── auth.php            # Endpoint Login, Logout, dan Cek Session
│   ├── barbers.php         # CRUD Data Kapster & Status Keaktifan
│   ├── dashboard.php       # Live Revenue, Pelanggan, & Tracker Harian
│   ├── payroll.php         # Perhitungan Otomatis Komisi Kapster
│   ├── services.php        # CRUD Harga Layanan & Komisi Default
│   ├── transactions.php    # Checkout Kasir POS (Subtotal, Diskon, Total)
│   └── users.php           # CRUD Akun Kasir & Manajemen Pengguna
├── config/
│   └── database.php        # Koneksi PDO MySQL (barberos_db)
├── includes/
│   └── helpers.php         # Utilities JSON, Auth Middleware & Currency Format
├── index.php               # Single Page Application Dashboard & POS
├── schema.sql              # DDL 5 Tabel Database & Data Seeder
├── test_db.php             # Skrip Verifikasi Status Database
└── README.md               # Dokumentasi Proyek
```

---

## 🔐 Akun Login Default
Buka di browser [http://localhost/BARDIR/](http://localhost/BARDIR/):

| Role | Email | Password | Hak Akses |
|---|---|---|---|
| **Owner** | `owner@barberos.local` | `password123` | Akses Penuh: Ubah Harga, Komisi, Kapster, Akun Kasir, Laporan Omset |
| **Kasir** | `cashier@barberos.local` | `password123` | Akses POS Kasir & Riwayat Transaksi |
