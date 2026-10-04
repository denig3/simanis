# STRUKTUR MATERI PRESENTASI 10 MENIT — SIMANIS
**Sistem Manajemen Inventaris & Order Multi-Gudang**  
**Final Project Intermediate Programmer**  
**Waktu Total:** Tepat 10 Menit (Termasuk Demo Ringkas)

---

## ⏱️ Manajemen Waktu & Alokasi Slide

| Slide | Topik Pembahasan | Alokasi Waktu | Target Kumulatif |
| :---: | :--- | :---: | :---: |
| **1** | Pembuka, Profil Proyek, & Latar Belakang SIMANIS | 45 detik | 00:00 – 00:45 |
| **2** | Alur Bisnis & Inventaris Multi-Gudang (PO, SO, GI, GR, Stock Ledger) | 90 detik | 00:45 – 02:15 |
| **3** | Pembatasan 3 Role & Segregation of Duties (SOD Enforcement) | 60 detik | 02:15 – 03:15 |
| **4** | Clean Layered Architecture & Dependency Inversion | 75 detik | 03:15 – 04:30 |
| **5** | Database ACID, Integritas Stok & Pencegahan Oversell (`SELECT FOR UPDATE`) | 90 detik | 04:30 – 06:00 |
| **6** | Fetch API Asinkron & Endpoint JSON `/api/products/{sku}/availability` | 45 detik | 06:00 – 06:45 |
| **7** | Kualitas Kode: Unit & Integration Test, PHPStan, & SonarQube Clean Code | 75 detik | 06:45 – 08:00 |
| **8** | Lingkungan DevOps: Docker Compose & Zero-Setup Automated Seeder | 45 detik | 08:00 – 08:45 |
| **9** | Dokumentasi Rekayasa: UML Class Diagram, ERD, ADR, & Refactoring Log | 45 detik | 08:45 – 09:30 |
| **10**| Kesimpulan & Sesi Tanya Jawab (Q&A Strategy) | 30 detik | 09:30 – 10:00 |

---

## 📑 Rincian Slide & Naskah Presenter (Speaker Script)

### SLIDE 1: Pembuka & Latar Belakang Proyek (00:00 – 00:45)
* **Judul Slide:** SIMANIS — Sistem Manajemen Inventaris & Order Multi-Gudang
* **Poin Visual:**
  - Logo SIMANIS & Tagline: *"Enterprise-Grade Inventory & Safe Order Processing"*
  - Stack: PHP 8.2 Native OOP, MySQL 8.0 InnoDB, Vanilla CSS (Color Hunt Theme), Docker Compose.
* **Naskah Presenter:**
  > *"Selamat pagi/siang Bapak/Ibu Dewan Penguji. Perkenalkan saya Deni. Hari ini saya mempresentasikan SIMANIS (Sistem Manajemen Inventaris & Order), solusi sistem inventaris multi-gudang yang dirancang untuk menjamin integritas data, keamanan transaksi tinggi, dan kepatuhan penuh terhadap prinsip tata kelola pemisahan tugas atau Segregation of Duties. Sistem ini dibangun dengan arsitektur bersih Native PHP 8.2, tanpa ketergantungan framework bloated, dan sepenuhnya berjalan di atas container Docker Compose."*

---

### SLIDE 2: Alur Inventory & Order Multi-Gudang (00:45 – 02:15)
* **Judul Slide:** Alur Operasional Terintegrasi: PO, SO, GI, GR & Buku Besar Mutasi
* **Poin Visual:**
  - Diagram Alur:
    - **Pengadaan (PO):** `Draft` ➔ `Sent to Supplier` ➔ `Goods Receipt (GR)` ➔ Stok Fisik Masuk + Audit Log `stock_ledger (Receipt)`.
    - **Penjualan (SO):** `Draft` ➔ `Pending Approval` ➔ `Approved` ➔ `Goods Issue (GI)` ➔ Stok Fisik Berkurang + Audit Log `stock_ledger (Issue)`.
  - Dukungan Multi-Gudang: Distribusi stok independen di Gudang Jakarta (JKT), Surabaya (SBY), dan Bandung (BDG).
* **Naskah Presenter:**
  > *"SIMANIS mengelola dua siklus utama pergudangan. Pertama, **Purchase Order**: Pengadaan barang dari supplier yang saat tiba di gudang diproses melalui **Goods Receipt (GR)**—baik parsial maupun penuh—yang secara atomik menambah stok fisik gudang dan mencatat mutasi di buku besar `stock_ledger`.  
  > Kedua, **Sales Order**: Pesanan penjualan dari pelanggan yang diajukan sales, divalidasi ketersediaan stoknya, lalu saat disetujui, staff gudang mengeksekusi **Goods Issue (GI)** untuk memotong stok fisik sesuai lokasi gudang yang ditunjuk. Seluruh perpindahan barang memiliki jejak audit lengkap (*audit trail*) di tabel `stock_ledger` sehingga tidak ada angka yang bisa berubah tanpa riwayat mutasi yang sah."*

---

### SLIDE 3: Pembatasan Tiga Role & Segregation of Duties (02:15 – 03:15)
* **Judul Slide:** Segregation of Duties (SOD): Matriks Hak Akses & Enforce Server-Side
* **Poin Visual:**
  - Matriks Role:
    - 👑 **Admin**: Mengelola Master Data, User, Monitoring Finansial, dan **Satu-satunya pihak yang berhak Approve/Reject Sales Order**.
    - 💼 **Sales Staff**: Membuat SO Draft, melihat katalog & stok, dilarang approve SO sendiri.
    - 📦 **Warehouse Staff**: Melakukan fisik Goods Receipt (PO) dan Goods Issue (SO), tanpa akses mengubah harga jual atau menyetujui pesanan.
* **Naskah Presenter:**
  > *"Prinsip inti dari sistem ini adalah **Segregation of Duties (SOD)**. Kasus fraud inventaris umumnya terjadi jika sales bisa menyetujui ordernya sendiri atau staf gudang bisa mengubah harga dan status order.  
  > Di SIMANIS, pembatasan ini tidak hanya di level antarmuka, melainkan ditegakkan secara ketat di server-side Controller dan Service. Sales dilarang menyetujui pesanan penjualannya sendiri; pesanan berstatus `pending_approval` wajib direview oleh Admin independen. Sebaliknya, Admin tidak melakukan fisik potong barang, melainkan didelegasikan kepada Warehouse Staff setelah pesanan berstatus sah disetujui."*

---

### SLIDE 4: Clean Layered Architecture & Dependency Inversion (03:15 – 04:30)
* **Judul Slide:** Arsitektur Berlapis (Clean Architecture) & Dekomposisi Tanggung Jawab
* **Poin Visual:**
  - Lapisan Arsitektur:
    1. **Presentation Layer:** Controller (`OrderController`, `DashboardController`, `AuthController`) — menangani HTTP, parsing request, CSRF, dan HTTP response.
    2. **Service Layer (Domain Logic):** `OrderService`, `DashboardService`, `ProductService` — aturan bisnis, validasi SOD, kalkulasi valuasi modal.
    3. **Domain Model:** Immutable & strictly typed (`Product`, `SalesOrder`, `PurchaseOrder`, `Warehouse`).
    4. **Data Access / Repository:** Interface (`OrderRepositoryInterface`) didecoupling dari implementasi nyata (`PdoOrderRepository`).
* **Naskah Presenter:**
  > *"Untuk memastikan kode mudah dipelihara dan diuji, SIMANIS menerapkan **Clean Layered Architecture**. Controller hanya bertugas menerima HTTP request dan otentikasi sesi. Seluruh logika bisnis dipusatkan di **Service Layer**.  
  > Hubungan antara Service dan Database menggunakan **Dependency Inversion Principle**: Service bergantung pada `OrderRepositoryInterface`, bukan kelas PDO langsung. Hal ini terbukti membuat Unit Testing kami sangat cepat dan terisolasi karena repository dapat di-mock atau di-stub tanpa perlu menyalakan database riil."*

---

### SLIDE 5: Transaksi Database & Pencegahan Oversell (04:30 – 06:00)
* **Judul Slide:** ACID Transactions & Concurrency-Safe Menggunakan Pessimistic Locking
* **Poin Visual:**
  - Cuplikan Query SQL Kunci:
    ```sql
    START TRANSACTION;
    -- Mengunci baris stok produk di gudang spesifik
    SELECT quantity FROM inventory_stocks 
    WHERE product_id = ? AND warehouse_id = ? FOR UPDATE;
    
    -- Validasi quantity >= order_qty, jika kurang ROLLBACK
    UPDATE inventory_stocks SET quantity = quantity - ? ...;
    INSERT INTO stock_ledger (...);
    UPDATE sales_orders SET status = 'fulfilled' ...;
    COMMIT;
    ```
* **Naskah Presenter:**
  > *"Tantangan terbesar sistem inventaris adalah **kondisi balapan (Race Condition)** saat beberapa transaksi penjualan terjadi bersamaan untuk stok barang yang terbatas. Jika menggunakan query standar, risiko stok menjadi negatif atau terjadi oversell sangat tinggi.  
  > SIMANIS mengatasi masalah ini menggunakan **Pessimistic Locking dengan klausa `SELECT ... FOR UPDATE`** di dalam transaksi ACID MySQL InnoDB. Ketika sebuah order dieksekusi Goods Issue, baris stok pada gudang tersebut dikunci secara eksklusif hingga commit selesai. Sesi lain yang mencoba membaca stok untuk transaksi serupa akan mengantre, menjamin konsistensi mutlak bahwa stok tidak akan pernah minus. Mekanisme ini telah kami buktikan dengan test konkurensi otomatis di `ConcurrencyStockIntegrationTest`."*

---

### SLIDE 6: Fetch API & Endpoint JSON Terpisah (06:00 – 06:45)
* **Judul Slide:** Interaksi Asinkron Asli (Fetch API) & Endpoint API Ketersediaan Stok
* **Poin Visual:**
  - Endpoint: `GET /api/products/{sku}/availability`
  - Response JSON: Status 200 OK dengan sebaran stok per gudang (JKT, SBY, BDG) dan total stok.
  - Form Pembuatan Order Asinkron dengan validasi stok instan dan proteksi CSRF Header.
* **Naskah Presenter:**
  > *"Antarmuka SIMANIS menggunakan **Vanilla JavaScript Fetch API** tanpa library pihak ketiga. Pengguna mendapatkan responsivitas real-time, seperti penambahan item pesanan dinamis dan modal interaktif.  
  > Kami juga menyediakan endpoint mandiri `GET /api/products/{sku}/availability` yang mengembalikan format JSON terstandarisasi untuk memeriksa ketersediaan stok lintas gudang secara instan. Seluruh panggilan `POST/PUT` dilindungi token CSRF di header request."*

---

### SLIDE 7: Kualitas Kode, Testing, & SonarQube (06:45 – 08:00)
* **Judul Slide:** Penjaminan Mutu: 31 Test Otomatis, PHPStan Level 6, & SonarQube Passed
* **Poin Visual:**
  - **PHPUnit 11**: 31 Tests, 147 Assertions — **100% Passed**.
  - **PHPStan**: Level 6 Strict Typing — **0 Errors across 67 files**.
  - **SonarQube Quality Gate**: **PASSED (0 Violations on New Code)**, Cognitive Complexity < 5, Duplicated Lines 0.0%.
* **Naskah Presenter:**
  > *"Aspek penjaminan mutu diimplementasikan secara komprehensif. Pertama, kami memiliki **31 pengujian otomatis PHPUnit** yang mencakup Unit Test logika bisnis serta Integration Test alur pesanan dan konkurensi stok.  
  > Kedua, analisis statis menggunakan **PHPStan Level 6** menghasilkan **0 error** di seluruh 67 berkas sumber berkat penerapan `declare(strict_types=1);` dan type hinting ketat.  
  > Ketiga, kode telah dipindai langsung ke server **SonarQube versi 26.9** dengan hasil **Quality Gate PASSED**. Kami telah merefaktor seluruh fungsi dengan kompleksitas tinggi menjadi sub-fungsi modular sehingga nol code smell dan nol bug."*

---

### SLIDE 8: Lingkungan Docker & Otomasi Seeder (08:00 – 08:45)
* **Judul Slide:** Docker Compose Multi-Container & Zero-Setup Automated Seeder
* **Poin Visual:**
  - Diagram Container:
    - Service `web-app-1`: PHP 8.2 Apache (port 8080)
    - Service `web-db-1`: MySQL 8.0 InnoDB (port 3306)
  - Otomasi: `DatabaseSeeder::seedIfEmpty()` otomatis menyiapkan database, migrasi tabel, dan akun demo siap pakai.
* **Naskah Presenter:**
  > *"Aplikasi ini 100% *reproducible*. Penguji atau tim penilai cukup menjalankan satu perintah: `docker compose up -d`.  
  > Melalui mekanisme automated seeder cerdas di kelas `DatabaseSeeder`, sistem akan mendeteksi jika tabel masih kosong, lalu secara otomatis menginisialisasi skema tabel, akun pengguna dengan enkripsi Bcrypt, 4 fasilitas gudang, katalog barang, serta contoh transaksi aktif. Tidak diperlukan impor database manual melalui phpMyAdmin atau SQL dump eksternal."*

---

### SLIDE 9: Dokumentasi Rekayasa & Standar Industri (08:45 – 09:30)
* **Judul Slide:** Technical Evidence: Class Diagram, ERD, ADR, & Refactoring Log
* **Poin Visual:**
  - Tautan Bukti Teknis Lengkap di Repositori:
    - `docs/CLASS_DIAGRAM.md` & `docs/planning/erd.md`
    - `docs/ADR.md` (Architecture Decision Records)
    - `docs/REFACTORING_LOG.md` (Catatan Evolusi Teknis)
    - `docs/SOP_USECASE_OPERASIONAL_SIMANIS.doc` (Panduan Operasional)
* **Naskah Presenter:**
  > *"Seluruh keputusan rekayasa perangkat lunak dicatat secara transparan. Kami menyertakan **ADR (Architecture Decision Record)** yang menjelaskan alasan teknis di balik pemilihan Pessimistic Locking, arsitektur Native OOP, serta eliminasi dependensi eksternal. Kami juga melampirkan **Log Refactoring** dan **SOP Operasional** lengkap sehingga kode ini siap dikembangkan lebih lanjut oleh tim pengembang dalam skala industri."*

---

### SLIDE 10: Kesimpulan & Penutup (09:30 – 10:00)
* **Judul Slide:** SIMANIS: Kokoh, Aman, dan Siap Produksi
* **Poin Visual:**
  - Rangkuman Keunggulan:
    1. Konsistensi Stok Terjamin (Zero Oversell)
    2. Kepatuhan Segregation of Duties (Anti-Fraud)
    3. 100% Tested & Clean Static Analysis
    4. Siap Dijalankan Mandiri via Docker
  - Tautan Repositori: `https://github.com/denig3/simanis`
* **Naskah Presenter:**
  > *"Sebagai penutup, SIMANIS berhasil membuktikan bahwa sistem pergudangan enterprise dapat dibangun secara kokoh, aman terhadap serangan konkurensi, dan patuh terhadap tata kelola SOD dengan performa tinggi tanpa framework rumit.  
  > Seluruh kode sumber, dokumentasi, dan hasil test dapat diakses di GitHub. Terima kasih atas perhatian Bapak/Ibu Dewan Penguji, waktu saya kembalikan untuk sesi tanya jawab."*
