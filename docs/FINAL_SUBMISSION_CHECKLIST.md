# CHECKLIST KESIAPAN FINAL PROJECT & UJIAN PRESENTASI
**Proyek:** SIMANIS (Sistem Manajemen Inventaris & Order Multi-Gudang)  
**Kandidat:** Deni  
**Repositori Resmi:** [https://github.com/denig3/simanis](https://github.com/denig3/simanis)  
**Status Evaluasi:** SIAP SUBMIT & SIAP PRESENTASI (100% Memenuhi Kriteria)

---

## 1. Verifikasi Checklist Utama

| Status | Item Pemeriksaan | Bukti Teknis & Lokasi File |
| :---: | :--- | :--- |
| [x] | **Repository telah dikumpulkan sebelum batas waktu dan dapat diakses.** | Repositori GitHub publik: [https://github.com/denig3/simanis](https://github.com/denig3/simanis), branch `main`, commit clean, public access terverifikasi. |
| [x] | **README telah diuji oleh orang lain atau dari environment bersih.** | File [README.md](file:///d:/Learn/Web/README.md) memuat petunjuk langkah instalasi lingkungan bersih (`cp .env.example .env`, `docker compose up -d`), akun demo standar, endpoint API, dan cara eksekusi test. |
| [x] | **Docker image dan seluruh service dapat dibangun dan dijalankan.** | File [compose.yaml](file:///d:/Learn/Web/compose.yaml) menjalankan `web-app-1` (PHP 8.2 Apache) dan `web-db-1` (MySQL 8.0 InnoDB), port 8080 terhubung mulus. |
| [x] | **Aplikasi, database, akun demo, dan data demo siap digunakan.** | [DatabaseSeeder.php](file:///d:/Learn/Web/src/Infrastructure/DatabaseSeeder.php) otomatis mengisi akun (`admin`, `sales1`, `sales2`, `gudang1`, `gudang2`), 4 gudang (JKT, SBY, BDG), 5 kategori, 5 SKU produk, serta order berjalan saat boot. |
| [x] | **Seluruh test yang dipersyaratkan dapat dijalankan dan hasilnya tersedia.** | **PHPUnit 11**: 31 / 31 test lulus (100%), 147 assertions (Unit & Integration Concurrency Test).<br>**PHPStan Level 6**: 0 errors pada 67 files.<br>Dokumentasi: [docs/quality/static-analysis-report.md](file:///d:/Learn/Web/docs/quality/static-analysis-report.md). |
| [x] | **Pemeriksaan SonarQube telah memenuhi ketentuan kelulusan.** | SonarQube versi 26.9.0.129388-community: **Quality Gate PASSED**.<br>0 Open Violations pada New Code (`caycStatus: compliant`), 0 duplicated lines density, konfigurasi [sonar-project.properties](file:///d:/Learn/Web/sonar-project.properties). |
| [x] | **Materi presentasi telah dilatih agar selesai dalam 10 menit.** | Slide deck interaktif di [public/presentation.html](file:///d:/Learn/Web/public/presentation.html) dan panduan narasi per menit di [docs/PRESENTATION_DECK_10_MINUTES.md](file:///d:/Learn/Web/docs/PRESENTATION_DECK_10_MINUTES.md). |

---

## 2. Inventaris Berkas Teknis Pendukung (Evidence Matrix)

### A. Arsitektur & Perancangan
- **Diagram Kelas UML:** [docs/CLASS_DIAGRAM.md](file:///d:/Learn/Web/docs/CLASS_DIAGRAM.md) — Struktur Clean Layered Architecture (`Controller` -> `Service` -> `Domain Model` -> `Repository Interface` -> `PdoRepository`).
- **Diagram Relasi Database (ERD):** [docs/planning/erd.md](file:///d:/Learn/Web/docs/planning/erd.md) dan visual interaktif di [public/erd.html](file:///d:/Learn/Web/public/erd.html).
- **Skema SQL Bersih & Indexing:** [database/schema.sql](file:///d:/Learn/Web/database/schema.sql) — Definisi DDL 10 tabel InnoDB dengan foreign keys, unique constraint, dan composite indexes.
- **Architecture Decision Record (ADR):** [docs/ADR.md](file:///d:/Learn/Web/docs/ADR.md) — Keputusan teknis arsitektur: Pessimistic Locking, Native OOP vs Framework, Vanilla CSS vs Tailwind, CSRF & Security Headers.
- **Log Refactoring:** [docs/REFACTORING_LOG.md](file:///d:/Learn/Web/docs/REFACTORING_LOG.md) — Riwayat pembersihan *technical debt*, dekomposisi kognitif, dan standardisasi interface.

### B. SOP & Skenario Pengujian Operasional
- **SOP Use Case Multi-Gudang & SOD:** [docs/SOP_USECASE_OPERASIONAL_SIMANIS.doc](file:///d:/Learn/Web/docs/SOP_USECASE_OPERASIONAL_SIMANIS.doc) dan [docs/SOP_USECASE_OPERASIONAL_SIMANIS.txt](file:///d:/Learn/Web/docs/SOP_USECASE_OPERASIONAL_SIMANIS.txt) — Alur lengkap PO, SO, Goods Receipt, Goods Issue, dan pembatasan peran 3 role.
- **Panduan Hak Akses Pengguna:** [docs/ACCESS_GUIDE.md](file:///d:/Learn/Web/docs/ACCESS_GUIDE.md) — Matriks izin akses per peran (Admin, Sales, Warehouse Staff).

### C. Pengujian Otomatis & Analisis Kualitas
- **Test Otomatis Unit & Integrasi:**
  - [tests/DashboardServiceTest.php](file:///d:/Learn/Web/tests/DashboardServiceTest.php) (Valuasi finansial, metrik stok, pipeline status order).
  - [tests/Integration/ConcurrencyStockIntegrationTest.php](file:///d:/Learn/Web/tests/Integration/ConcurrencyStockIntegrationTest.php) (Uji konkurensi Goods Issue dan pencegahan oversell).
  - [tests/Integration/PurchaseOrderWorkflowIntegrationTest.php](file:///d:/Learn/Web/tests/Integration/PurchaseOrderWorkflowIntegrationTest.php) (Uji siklus PO -> Partial/Full Goods Receipt).
- **Laporan Static Analysis:** [docs/quality/static-analysis-report.md](file:///d:/Learn/Web/docs/quality/static-analysis-report.md).
- **Konfigurasi SonarQube Scanner:** [sonar-project.properties](file:///d:/Learn/Web/sonar-project.properties).
