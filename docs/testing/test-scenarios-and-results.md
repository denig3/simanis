# Skenario Pengujian & Bukti Hasil Eksekusi (docs/testing/)

Dokumen ini memuat matriks skenario pengujian unit, pengujian integrasi, penanganan kegagalan (*failure paths*), serta bukti eksekusi perintah pengujian.

---

## 1. Matriks Skenario Pengujian Unit (TEST-01)

Dijalankan secara terisolasi dengan `InMemoryOrderRepository` (Zero Database Dependency):

| ID Test | Area Logika | Deskripsi Skenario Pengujian | Ekspektasi | Hasil Aktual |
| :--- | :--- | :--- | :--- | :---: |
| **UT-01** | Segregation of Duties | Sales mencoba menyetujui Sales Order miliknya sendiri | Menolak dengan `ForbiddenException (403)` | **PASS** |
| **UT-02** | Segregation of Duties | Pembuat order (walaupun Admin) mencoba menyetujui order buatannya | Menolak dengan `ForbiddenException (403)` | **PASS** |
| **UT-03** | Segregation of Duties | Admin independen menyetujui order buatan Sales | Berhasil disetujui (`status = approved`) | **PASS** |
| **UT-04** | Status Transition | Mengajukan order yang bukan berstatus `draft` | Menolak dengan `ConflictException (409)` | **PASS** |
| **UT-05** | Status Transition | Memproses Goods Issue untuk order yang belum `approved` | Menolak dengan `ConflictException (409)` | **PASS** |
| **UT-06** | Status Transition | Membatalkan Purchase Order yang sudah diterima | Menolak dengan `ConflictException (409)` | **PASS** |
| **UT-07** | Concurrency & Stok | Goods Issue ditolak ketika stok fisik gudang tidak mencukupi | Menolak dengan `InsufficientStockException (409)` | **PASS** |
| **UT-08** | Mutasi Stok Atomik | Goods Issue berhasil mengurangi stok & mencatat audit trail ledger | Stok berkurang, `StockLedger` mencatat pergerakan negatif | **PASS** |
| **UT-09** | Mutasi Stok Atomik | Goods Receipt menambah saldo stok & mencatat audit trail ledger | Stok bertambah, `StockLedger` mencatat pergerakan positif | **PASS** |
| **UT-10** | API Availability | Request ketersediaan untuk SKU yang tidak ada | Menolak dengan `NotFoundException (404)` | **PASS** |
| **UT-11** | API Availability | Request ketersediaan untuk SKU valid | Mengembalikan struktur JSON stok per gudang | **PASS** |

---

## 2. Matriks Skenario Pengujian Integrasi (TEST-02)

Dijalankan langsung terhadap database MySQL 8 di lingkungan Docker Compose:

| ID Test | Skenario Pengujian | Rincian Eksekusi | Hasil |
| :--- | :--- | :--- | :---: |
| **IT-01** | Goods Receipt End-to-End | PO dibuat, penerimaan barang diproses via PDO, memverifikasi penambahan `quantity_on_hand` di tabel `inventory_stocks` dan baris baru di `stock_ledger`. | **PASS** |
| **IT-02** | Goods Issue End-to-End | SO disetujui, pengeluaran barang diproses, memverifikasi pengurangan stok fisik dan pencatatan audit trail ledger bertipe `GOODS_ISSUE`. | **PASS** |
| **IT-03** | Concurrency / Oversell Prevention (ARCH-02) | Stok fisik dibatasi 5 unit. Order 1 meminta 4 unit, Order 2 meminta 3 unit. Kedua order disetujui. Order 1 diproses (stok sisa 1). Order 2 diproses bersamaan: sistem mengunci baris dengan `SELECT ... FOR UPDATE`, mendeteksi stok tidak cukup, membatalkan transaksi (`ROLLBACK`), dan melempar `InsufficientStockException`. Stok fisik terbukti aman tidak menjadi minus. | **PASS** |

---

## 3. Bukti Eksekusi Pengujian (Command Output)

### Perintah 1: Eksekusi Unit Test (Cepat & Mandiri)
```bash
$ vendor/bin/phpunit --testsuite Unit
PHPUnit 11.5.56 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: D:\Learn\Web\phpunit.xml

........................                                          24 / 24 (100%)

Time: 00:00.885, Memory: 10.00 MB

OK (24 tests, 82 assertions)
```

### Perintah 2: Eksekusi di Lingkungan Docker
```bash
$ docker compose exec app vendor/bin/phpunit
PHPUnit 11.5.56 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: /var/www/html/phpunit.xml

...........................                                       27 / 27 (100%)

Time: 00:02.140, Memory: 14.00 MB

OK (27 tests, 91 assertions)
```
