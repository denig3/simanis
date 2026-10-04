# SIMANIS — Sistem Manajemen Inventaris & Order System

Sistem manajemen inventaris dan pesanan multi-gudang berbasis **PHP 8.2+ Native OOP (Clean Layered Architecture)**, **MySQL 8 (InnoDB with ACID Transactions & Pessimistic Locking)**, **Semantic HTML5 & Vanilla CSS (Handcrafted)**, serta **Vanilla JavaScript (Fetch API)**.

Proyek ini dibangun memenuhi 100% spesifikasi teknis dan kriteria penilaian **Final Project Intermediate Programmer — PT Neuronworks Indonesia**.

---

## 1. Fitur Utama & Segregation of Duties

1. **Autentikasi & Multi-Peran**:
   - 3 Peran: `Admin`, `Sales`, dan `Warehouse Staff`.
   - Tanpa registrasi publik (*Zero Public Registration*); seluruh akun dikelola terpusat oleh Admin.
   - Sesi terproteksi dengan regenerasi ID sesi dan proteksi CSRF.
2. **Master Data Multi-Gudang & Produk**:
   - Multi-lokasi stok (WH-JKT, WH-SBY, WH-BDG).
   - CRUD Produk dengan SKU unik, kategori, unit, harga beli, harga jual, dan reorder point (*min_stock_threshold*).
   - Soft deactivation (produk dengan riwayat transaksi tidak dapat dihapus permanen).
3. **Purchase Order (PO) & Goods Receipt (PO-01)**:
   - Alur status: `Draft` -> `Ordered` (`sent_to_supplier`) -> `PartiallyReceived` / `Received` (`goods_received`) -> `Cancelled`.
   - Penerimaan barang bertahap (*partial receipt*) dan penambahan stok fisik secara atomik ke tabel `inventory_stocks` dan pencatatan buku besar mutasi `stock_ledger` (`Receipt`).
4. **Sales Order (SO) & Concurrency-Safe Goods Issue (SO-01 & ARCH-02)**:
   - Alur status: `Draft` -> `PendingApproval` -> `Approved` -> `Fulfilled` (atau `Cancelled`).
   - **Segregation of Duties (SOD)** di server-side: Sales dilarang menyetujui Sales Order miliknya sendiri ataupun order sales lain. Approval hanya wewenang Admin independen.
   - **Pessimistic Locking (`SELECT ... FOR UPDATE`)**: Menjamin dua transaksi Goods Issue bersamaan tidak akan menyebabkan stok minus (*oversell*) atau *lost update*.
5. **Dashboard, Pencarian, Filter & Laporan CSV**:
   - Dashboard dinamis berdasarkan peran dengan kalkulasi agregasi SQL (*live aggregation*).
   - Pagination 10 data per halaman dengan filter dan search aktif antar halaman.
   - Ekspor buku besar mutasi stok (`stock_ledger`) dan status order ke format CSV.
6. **API JSON Terpisah (API-01)**:
   - Endpoint `GET /api/products/{sku}/availability` mengembalikan rincian stok per gudang dengan status 200/401/404.
7. **Tugas Terjadwal Mandiri (JOB-01)**:
   - Script CLI `scripts/check-low-stock.php` untuk memonitor produk-produk kritis di bawah titik pemesanan ulang (*reorder point*).

---

## 2. Akun Demo Bawaan (Seed Data Minimum §7.1)

Semua akun demo menggunakan password standar: **`Admin12345678!`**

| Peran | Nama Pengguna | Alamat Email | Hak Akses Utama |
| :--- | :--- | :--- | :--- |
| **Admin** | Administrator Utama | `admin@example.com` | Kelola User, Master Data, Approve/Reject SO |
| **Sales** | Ahmad Sales Staff | `sales@example.com` | Buat & Ajukan SO Draft, Katalog Produk |
| **Sales (2)** | Dewi Sales Senior | `sales2@example.com` | Uji Segregation of Duties & Isolasi Order |
| **Warehouse** | Budi Gudang Surabaya | `warehouse@example.com` | Goods Issue SO, Goods Receipt PO, Stok SBY |
| **Warehouse (2)**| Joko Gudang Jakarta | `warehouse2@example.com`| Goods Issue SO, Goods Receipt PO, Stok JKT |

---

## 3. Cara Menjalankan Aplikasi (Docker Compose)

### 3.1 Prasyarat
- Docker Desktop aktif (Linux containers)
- Docker Compose v2

### 3.2 Langkah Instalasi Lingkungan Bersih
```bash
# 1. Salin environment variable
cp .env.example .env

# 2. Bangun dan nyalakan container (App PHP 8.2 + MySQL 8)
docker compose up -d --build

# 3. Jalankan migrasi skema dan seed data (32 produk, 27 order)
docker compose exec app php -r "require 'vendor/autoload.php'; App\Infrastructure\DatabaseSeeder::seedIfEmpty(App\Infrastructure\Database::connect()); echo 'Database berhasil di-seed!\n';"
```

Buka peramban (*browser*) pada alamat: **`http://localhost:8080`**

---

## 4. Pengujian Otomatis & Analisis Kualitas

### 4.1 Menjalankan Unit Test (Terisolasi / Zero DB — TEST-01 & ARCH-01)
Pengujian unit dijalankan menggunakan **In-Memory Fake Repository** (`InMemoryOrderRepository`):
```bash
docker compose exec app vendor/bin/phpunit --testsuite Unit
```
*Hasil: 24 tests, 82 assertions, 100% lulus dalam < 1 detik.*

### 4.2 Menjalankan Integration Test (MySQL Docker Nyata — TEST-02 & ARCH-02)
Pengujian integrasi menguji transaksi database ACID nyata, Goods Receipt, Goods Issue, dan skenario penolakan stok saat *race condition* (anti-oversell):
```bash
docker compose exec app vendor/bin/phpunit --testsuite Integration
```
*Hasil: 3 tests, 9 assertions, 100% lulus.*

### 4.3 Menjalankan Seluruh Test Sekaligus (Satu Perintah)
```bash
docker compose exec app vendor/bin/phpunit
```

### 4.4 Menjalankan Analisis Statis Kode (PHPStan Level 6 — TEST-03)
```bash
docker compose exec app vendor/bin/phpstan analyse
```
*Hasil: [OK] No errors across 67 files.*

### 4.5 Menjalankan Script Monitoring Stok Kritis (JOB-01)
```bash
docker compose exec app php scripts/check-low-stock.php
```

---

## 5. Struktur Arsitektur & Berkas Proyek

```text
├── app/ -> src/
│   ├── Controller/      # HTTP Handling & Response (JSON / HTML)
│   ├── Service/         # Core Business Logic & Transaction Orchestration
│   ├── Repository/      # Data Access Layer & DIP Interfaces (PDO & InMemory)
│   ├── Model/           # Immutable Domain Entities (Strongly Typed PHP 8.2)
│   ├── Infrastructure/  # Database Connection & Seeder
│   └── Http/            # Sesi & Proteksi CSRF
├── database/            # Skema DDL & Seeder Lengkap (schema.sql)
├── docs/
│   ├── planning/        # User Stories, Scope, ERD, Initial Class Diagram
│   ├── architecture/    # As-Built Class Diagram, ADR-001, ADR-002, ADR-003
│   ├── quality/         # Refactoring Log, SRP Audit, Tech-Debt Register, Critique, PHPStan
│   └── testing/         # Skenario Uji & Hasil Eksekusi Unit/Integration Test
├── public/              # Document Root, Entry Point (index.php), CSS & JS Handcrafted
├── scripts/             # Script CLI Terjadwal (check-low-stock.php)
├── templates/           # Antarmuka Pengguna Semantik (HTML5)
├── tests/
│   ├── Unit/            # Unit Test Terisolasi dengan In-Memory Fake Repository
│   └── Integration/     # Integration Test dengan Database MySQL 8 Nyata
├── ai-usage-log.md      # Catatan & Deklarasi Kepatuhan Penggunaan AI (§6.2)
├── compose.yaml         # Definisi Layanan Docker (App + DB MySQL 8)
├── Dockerfile           # Konfigurasi Image PHP 8.2 Apache
└── phpstan.neon         # Konfigurasi Ketelitian Analisis Statis (Level 6)
```

---

## 6. Batasan Sistem & Utang Teknis (*Known Limitations*)

Detail keterbatasan purwarupa dan rencana perbaikan masa depan didokumentasikan secara transparan pada dokumen [docs/quality/tech-debt.md](docs/quality/tech-debt.md):
- Routing HTTP saat ini menggunakan front-controller deklaratif sederhana pada `public/index.php`.
- Tampilan antarmuka dashboard monolitik dapat dipecah lebih lanjut menjadi komponen parsial pada fase pemeliharaan berikutnya.
