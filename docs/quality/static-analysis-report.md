# Laporan Analisis Statis Kode (TEST-03: Static Analysis Report)

**Alat Analisis**: PHPStan (PHP Static Analysis Tool)  
**Tingkat Ketelitian (*Rule Level*)**: Level 6 (Melampaui syarat minimum Level 5+)  
**Target Analisis**: Seluruh kode sumber (`src/`, `public/`, `tests/`, `scripts/`)  
**Hasil**: **0 Errors (100% Pass / Nol Critical Error)**  

---

## 1. Bukti Eksekusi Perintah

```bash
$ vendor/bin/phpstan analyse
Note: Using configuration file D:\Learn\Web\phpstan.neon.
  0/67 [>---------------------------]   0% 67/67 [============================] 100%

 [OK] No errors
```

---

## 2. Metrik Kepatuhan

| Kategori Evaluasi | Standar Brief | Hasil Aktual Sistem | Status |
| :--- | :---: | :---: | :---: |
| **PHPStan Level** | Minimum Level 5+ | **Level 6** | Terpenuhi |
| **Jumlah File Teranalisis** | Seluruh basis kode | **67 berkas** | Terpenuhi |
| **Total Critical Error** | 0 Error | **0 Error** | Terpenuhi |
| **Type Safety & Docblocks** | Strict Types (`declare(strict_types=1);`) | Diterapkan pada 100% berkas PHP | Terpenuhi |
| **Null Safety & Generics** | Anotasi array tipe spesifik (`list<SalesOrder>`) | Dilengkapi pada seluruh interface & service | Terpenuhi |

---

## 3. Kepatuhan Terhadap Prinsip F.I.R.S.T. pada Pengujian

Seluruh pengujian unit pada sistem STOKORA mematuhi prinsip **FIRST**:

1. **F — Fast (Cepat)**:
   - Pengujian unit `tests/Unit/OrderServiceTest.php` dan service lainnya menggunakan implementasi `InMemoryOrderRepository` dan mock terisolasi.
   - 24 unit test selesai dieksekusi dalam waktu **< 1 detik** (rata-rata 0,88 detik).
2. **I — Independent (Terisolasi / Bebas Efek Samping)**:
   - Setiap test case menginisialisasi state baru di dalam metode `setUp()`. Tidak ada state global yang bocor antar pengujian.
3. **R — Repeatable (Dapat Diulang Kapan Saja)**:
   - Pengujian unit menghasilkan luaran yang 100% identik di setiap lingkungan tanpa bergantung pada koneksi internet, timezone, atau database server.
4. **S — Self-Validating (Validasi Otomatis)**:
   - Seluruh hasil divalidasi melalui assertion PHPUnit (`assertSame`, `assertCount`, `expectException`). Tidak memerlukan inspeksi manual mata manusia.
5. **T — Timely (Tepat Waktu)**:
   - Pengujian unit ditulis selaras dengan perancangan arsitektur untuk menjamin keamanan regresi saat dilakukan refactoring (*Safe-Refactor Demo*).
   - **Bebas `sleep()`**: Tidak ada penggunaan fungsi penundaan waktu artifisial (`sleep()` atau `usleep()`) di dalam kode pengujian.
