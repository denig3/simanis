# ADR 002: Pencegahan Oversell Melalui Pessimistic Locking & Transaksi ACID

- **Status**: Accepted
- **Tanggal**: Oktober 2026
- **Konteks**: Intermediate Programmer Final Project (ARCH-02, SO-01)

---

## 1. Konteks & Masalah
Pada sistem inventaris multigudang, risiko fatal paling tinggi adalah terjadinya **kondisi balapan (*race condition*)** dan **stok negatif (*oversell*)**. 
Skenario:
- Produk A memiliki sisa stok 5 unit di Gudang Jakarta.
- Dua permintaan pengeluaran barang (Goods Issue) untuk Sales Order berbeda tiba di server pada milidetik yang hampir bersamaan (misal SO-1 meminta 4 unit, SO-2 meminta 3 unit; total kebutuhan = 7 unit).
- Tanpa penguncian data tingkat baris (*row-level lock*), kedua thread membaca bahwa stok = 5 (cukup). Keduanya melakukan pengurangan stok secara terpisah, sehingga saldo akhir menjadi -2 (stok minus), atau transaksi saling menimpa (*lost update*).

## 2. Keputusan Arsitektur
Kami memilih mekanisme **Pessimistic Concurrency Control** dengan memanfaatkan fitur bawaan engine MySQL 8 InnoDB:
1. Setiap eksekusi pengeluaran barang (Goods Issue) dan penerimaan barang (Goods Receipt) dibungkus dalam blok transaksi database eksplisit (`beginTransaction()` ... `commit()` / `rollBack()`).
2. Sebelum memeriksa kecukupan stok dan sebelum melakukan pembaruan, aplikasi mengeksekusi query:
   ```sql
   SELECT quantity_on_hand 
   FROM inventory_stocks 
   WHERE product_id = ? AND warehouse_id = ? 
   FOR UPDATE;
   ```
3. Pernyataan `FOR UPDATE` mengunci baris stok produk di gudang spesifik tersebut secara eksklusif. Transaksi kedua yang mencoba membaca baris yang sama dengan `FOR UPDATE` akan **diblokir dan menunggu (wait)** sampai transaksi pertama selesai melakukan `commit` atau `rollBack`.
4. Setelah kunci didapat, aplikasi mengevaluasi:
   - Jika `quantity_on_hand < requested_qty`, sistem melempar `InsufficientStockException`, memanggil `rollBack()`, dan permintaan kedua ditolak dengan aman.
   - Jika mencukupi, aplikasi mengurangi stok fisik, menulis satu baris mutasi ke tabel immutable `stock_ledger`, memperbarui status Sales Order menjadi `fulfilled`, lalu memanggil `commit()`.
5. Sebagai *safety net* lapis kedua di level skema tabel, ditambahkan Check Constraint:
   ```sql
   CONSTRAINT chk_stk_qty_positive CHECK (quantity_on_hand >= 0)
   ```

## 3. Mengapa Tidak Optimistic Locking?
- Optimistic locking (kolom versioning) mengharuskan aplikasi menangani *retry loop* di level aplikasi saat terjadi collision.
- Pada sistem inventaris pergudangan (*physical warehouse*), jika stok fisik memang tidak cukup, me-retry transaksi tidak akan mengubah kenyataan bahwa barangnya memang habis. Oleh karena itu, Pessimistic Locking jauh lebih deterministik, aman, dan langsung menolak pesanan yang tidak memenuhi syarat tanpa overhead retry loop.

## 4. Konsekuensi
### Positif:
- **Zero Oversell Guarantee**: Menjamin 100% integritas data stok fisik dan mencegah stok negatif.
- **Konsistensi Audit Trail**: Setiap perubahan stok dijamin tercatat di `stock_ledger`.
### Negatif / Biaya:
- Ada sedikit latency antrean *lock wait* jika transaksi yang mengunci baris memakan waktu lama. Namun karena transaksi Goods Issue kita dirancang sangat cepat (hanya operasi database murni dalam sub-10ms tanpa panggilan network eksternal di dalam lock), latency ini dapat diabaikan.
