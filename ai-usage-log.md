# Catatan Penggunaan Kecerdasan Buatan (AI Usage Log)

**Proyek**: SIMANIS (Sistem Manajemen Inventaris & Order Management System)  
**Kepatuhan**: Mengikuti Ketentuan Integritas Proses §6.2 (Disclose, Review, Verify, Test)

---

## 1. Kebijakan Integritas & Deklarasi
Sesuai dengan pedoman teknis program:
1. **Disclose**: Seluruh interaksi dengan asisten pemrograman AI dicatat secara transparan.
2. **Review**: Setiap saran arsitektur dan struktur kode ditinjau kesesuaiannya dengan prinsip Clean Architecture dan larangan overengineering.
3. **Verify**: Setiap query database, prepared statement, dan penguncian transaksi diverifikasi terhadap standar keamanan MySQL 8 InnoDB.
4. **Test**: Kode hasil iterasi diuji secara mandiri menggunakan PHPUnit (Unit & Integration) serta static analysis PHPStan Level 6 dengan 0 errors.

---

## 2. Log Interaksi dan Verifikasi

| Tanggal | Alat AI | Tujuan Rekayasa | Ringkasan Prompt Disanitasi | Output yang Digunakan / Ditolak | Bukti Verifikasi |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **04 Okt 2026** | Antigravity AI Assistant | Perancangan arsitektur concurrency control dan penegakan Segregation of Duties | "Bantu rancang mekanisme pencegahan race condition pada Goods Issue menggunakan transaksi eksplisit dan pessimistic lock MySQL 8 sesuai ARCH-02" | **Digunakan**: Pola `SELECT ... FOR UPDATE` dalam `beginTransaction/commit` dan validasi kuantitas.<br>**Ditolak**: Saran penggunaan event dispatcher eksternal atau message queue (karena overengineering & dilarang brief). | Teruji pada `tests/Unit/OrderServiceTest.php` dan `tests/Integration/ConcurrencyStockIntegrationTest.php` |
| **04 Okt 2026** | Antigravity AI Assistant | Desain Dependency Inversion pada Repository Layer | "Buat kontrak OrderRepositoryInterface dan dua implementasi konkret: MySQL PDO dan In-Memory Fake untuk pengujian unit terisolasi" | **Digunakan**: `OrderRepositoryInterface`, `PdoOrderRepository`, dan `InMemoryOrderRepository`. Seluruh dependency diinjeksi via constructor. | Unit test 24/24 pass dalam 0.88 detik tanpa koneksi database |
| **04 Okt 2026** | Antigravity AI Assistant | Analisis Statis Kode & Pengujian | "Jalankan PHPStan Level 6 dan verifikasi tidak ada error tipe atau warning pada seluruh file PHP" | **Digunakan**: Penambahan anotasi tipe eksplisit generics array (`list<SalesOrder>`, `list<array{product_id: int, quantity: int}>`). | PHPStan Level 6: 0 errors across 67 files |
| **04 Okt 2026** | Antigravity AI Assistant | Dokumentasi Arsitektur & Evidence | "Bantu susun dokumen ADR-01, ADR-02, ADR-03, Class Diagram (Initial vs As-Built), Critique Exercise, dan Technical Debt Register" | **Digunakan**: Format markdown standar industri dan diagram Mermaid yang akurat sesuai kode aktual. | Assessor dapat menelusuri setiap kelas dari diagram Mermaid langsung ke kode sumber |
