# Dokumen Perencanaan: Scope, User Stories & Backlog

**Proyek**: Inventory & Order Management System (STOKORA)  
**Program**: Intermediate Programmer Final Project — PT Neuronworks Indonesia  
**Tanggal**: Oktober 2026  

---

## 1. Lingkup Proyek (Scope)

### 1.1 In-Scope (Fitur Wajib)
1. **Autentikasi & Otorisasi Sesi Multi-Peran**:
   - Login, logout, proteksi route server-side, regenerasi session ID.
   - 3 Peran: `Admin`, `Sales`, `Warehouse Staff`.
   - Tidak ada registrasi publik (*Zero Public Registration*). Seluruh akun dibuat dan dikelola oleh Admin.
2. **Master Data Multi-Gudang & Produk**:
   - CRUD Produk dengan SKU unik, kategori, unit, harga beli, harga jual, reorder point (min_stock_threshold).
   - Multi-gudang (WH-JKT, WH-SBY, WH-BDG).
   - Soft-deactivation (produk yang sudah memiliki relasi order tidak dihapus permanen).
   - Master data Customer & Supplier.
3. **Purchase Order (PO) & Penerimaan Barang (Goods Receipt)**:
   - Alur status: `Draft` -> `Ordered` (`sent_to_supplier`) -> `PartiallyReceived` / `Received` (`goods_received`) -> `Cancelled`.
   - Goods Receipt menambah stok fisik gudang dan mencatat riwayat ke `StockLedger` (pergerakan: `Receipt`) dalam 1 transaksi atomik.
   - Mendukung penerimaan sebagian (*partial receipt*).
4. **Sales Order (SO) & Pengeluaran Barang (Goods Issue)**:
   - Alur status: `Draft` -> `PendingApproval` -> `Approved` -> `Fulfilled` (atau `Cancelled`).
   - Penegakan **Segregation of Duties (SOD)** di server-side: Sales dilarang menyetujui Sales Order siapapun (termasuk miliknya sendiri). Approval hanya oleh Admin.
   - Goods Issue hanya untuk order status `Approved`.
   - Concurrency-safe: Menggunakan Pessimistic Locking (`SELECT ... FOR UPDATE`) pada `inventory_stocks` untuk mencegah *oversell* / balapan data jika dua order diproses bersamaan.
   - Pengurangan stok dan penulisan `StockLedger` (pergerakan: `Issue`) dieksekusi dalam satu transaksi atomik.
5. **Dashboard, Pencarian, & Laporan**:
   - Dashboard dinamis berdasarkan peran pengguna menggunakan query agregasi SQL.
   - Pagination 10 baris per halaman dengan filter dan search aktif antar halaman.
   - Ekspor laporan CSV mutasi stok dan ringkasan order.
6. **API JSON Terpisah**:
   - Endpoint `GET /api/products/{sku}/availability` mengembalikan rincian stok per gudang dengan status HTTP 200/401/404.
7. **Scheduled Task (JOB-01)**:
   - Script CLI mandiri `scripts/check-low-stock.php` untuk memonitor produk di bawah reorder point.

### 1.2 Out-of-Scope (Batas Desain)
- Microservices, message queue eksternal (RabbitMQ/Kafka), event sourcing, CQRS terpisah.
- Cloud Kubernetes, automated CI/CD pipeline, mobile native application.
- Real-time WebSockets / push notification.

---

## 2. User Stories Berdasarkan Peran

### 2.1 Peran: Admin
- **US-ADM-01**: Sebagai Admin, saya ingin membuat dan mengelola akun pengguna (Sales & Warehouse Staff) sehingga akses ke sistem terkontrol dan aman tanpa registrasi terbuka.
- **US-ADM-02**: Sebagai Admin, saya ingin mengelola master data produk, kategori, gudang, supplier, dan customer sehingga operasional bisnis berjalan dengan data yang valid.
- **US-ADM-03**: Sebagai Admin, saya ingin meninjau (*review*) Sales Order yang diajukan oleh tim Sales dan memutuskan untuk menyetujui (*Approve*) atau menolak (*Reject*), dengan aturan ketat bahwa saya tidak boleh menyetujui order buatan saya sendiri jika saya pernah membuat draft.
- **US-ADM-04**: Sebagai Admin, saya ingin melihat dashboard menyeluruh yang mencakup nilai inventaris total, daftar produk kritis di bawah reorder point, dan antrean approval.

### 2.2 Peran: Sales Staff
- **US-SLS-01**: Sebagai Sales Staff, saya ingin melihat katalog produk beserta stok tersedia di setiap gudang agar saya dapat memberikan informasi akurat kepada pelanggan.
- **US-SLS-02**: Sebagai Sales Staff, saya ingin membuat Sales Order berstatus `Draft` dan menambahkan beberapa item pesanan pelanggan.
- **US-SLS-03**: Sebagai Sales Staff, saya ingin mengajukan Sales Order (`Draft` -> `PendingApproval`) agar dapat ditinjau oleh Admin, dan saya sadar bahwa saya tidak memiliki wewenang untuk menyetujui order saya sendiri.
- **US-SLS-04**: Sebagai Sales Staff, saya ingin memantau status pesanan saya dan mengunduh laporan penjualan saya dalam format CSV.

### 2.3 Peran: Warehouse Staff
- **US-WHS-01**: Sebagai Warehouse Staff, saya ingin melihat daftar stok barang di gudang saya dan menerima peringatan ketika ada stok produk yang berada di bawah titik pemesanan ulang (*reorder point*).
- **US-WHS-02**: Sebagai Warehouse Staff, saya ingin mengusulkan atau membuat draft Purchase Order ke supplier ketika barang menipis.
- **US-WHS-03**: Sebagai Warehouse Staff, saya ingin mencatat penerimaan barang (*Goods Receipt*) dari Purchase Order yang datang, baik penerimaan penuh maupun bertahap (*partial*), sehingga saldo stok bertambah secara otomatis.
- **US-WHS-04**: Sebagai Warehouse Staff, saya ingin memproses pengeluaran barang (*Goods Issue*) untuk Sales Order yang telah berstatus `Approved`, dengan jaminan sistem bahwa proses ini tidak akan menyebabkan stok negatif meskipun ada transaksi bersamaan.
- **US-WHS-05**: Sebagai Warehouse Staff, saya ingin mengunduh laporan buku besar mutasi stok (*Stock Ledger CSV*) untuk keperluan audit fisik (*stock opname*).

---

## 3. Product Backlog

| ID | Modul | Deskripsi Pekerjaan | Prioritas | Status |
| :--- | :--- | :--- | :---: | :---: |
| **PB-01** | Database | Desain skema relasional MySQL 8, constraint `quantity >= 0`, foreign keys, dan index | P0 | Selesai |
| **PB-02** | Database | Seeder data awal: 5 user, 3 gudang, 4 kategori, 32 produk, 27 order (PO+SO) | P0 | Selesai |
| **PB-03** | Auth | Session security, password hashing, proteksi otorisasi server-side | P0 | Selesai |
| **PB-04** | Master Data | CRUD & Soft deactivation untuk Produk, Gudang, Kategori, Mitra | P0 | Selesai |
| **PB-05** | Purchase Order | Pembuatan PO, status transition, Goods Receipt transaksional | P0 | Selesai |
| **PB-06** | Sales Order | Pembuatan SO, SOD approval guard, Goods Issue dengan Pessimistic Lock | P0 | Selesai |
| **PB-07** | Stock Ledger | Pencatatan mutasi atomik immutable ledger setiap Receipt/Issue | P0 | Selesai |
| **PB-08** | API | Endpoint `GET /api/products/{sku}/availability` format JSON | P0 | Selesai |
| **PB-09** | CLI Job | Script mandiri `scripts/check-low-stock.php` | P1 | Selesai |
| **PB-10** | Testing | Unit test terisolasi (Fake InMemory Repo) & Integration Test (MySQL Docker) | P0 | Selesai |
| **PB-11** | QA | Static analysis PHPStan Level 6 (0 errors) & Security audit | P0 | Selesai |
| **PB-12** | Dokumentasi | ADR-01, ADR-02, ADR-03, Class diagram, Refactor log, Critique | P0 | Selesai |
