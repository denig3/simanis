# ADR 003: Penegakan Segregation of Duties (SOD) pada Server-Side

- **Status**: Accepted
- **Tanggal**: Oktober 2026
- **Konteks**: Intermediate Programmer Final Project (§1.2 & SO-01)

---

## 1. Konteks & Masalah
Dalam sistem Enterprise Resource Planning (ERP), manajemen keuangan, dan pergudangan industri, prinsip **Segregation of Duties (Pemisahan Tugas)** adalah kontrol kepatuhan (*internal control*) paling fundamental untuk mencegah kecurangan (*fraud*) dan penyalahgunaan wewenang:
- Sales yang membuat penawaran/order penjualan tidak boleh memiliki kuasa untuk menyetujui (*approve*) order tersebut.
- Jika pengguna yang sama dapat membuat dan sekaligus menyetujui transaksi, mereka dapat memanipulasi diskon, harga jual fiktif, atau pengiriman barang tanpa pengawasan manajemen.
- Masalah umum pada implementasi pemula adalah **hanya menyembunyikan tombol "Approve" di sisi UI (JavaScript / CSS)**, sementara endpoint API `/api/orders/sales/approve` tetap dapat ditembak secara langsung menggunakan Postman, cURL, atau DevTools console.

## 2. Keputusan Arsitektur
Kami memutuskan untuk menegakkan aturan **Segregation of Duties mutlak di lapisan server (Authorization & Service Layer)**:
1. **Pemeriksaan Role Server-Side**:
   - Hanya pengguna dengan peran `admin` yang berhak menyetujui Sales Order. Permintaan dari peran `sales` atau `warehouse` langsung menghasilkan response `403 Forbidden` (`ForbiddenException`).
2. **Pemeriksaan Kepemilikan Transaksi (*Self-Approval Guard*)**:
   - Di dalam `OrderService::approveSalesOrder`:
     ```php
     if ($so->createdBy === (int) $user['id']) {
         throw new ForbiddenException('Pelanggaran Segregation of Duties: Pembuat order dilarang menyetujui order miliknya sendiri.');
     }
     ```
   - Aturan ini berlaku bahkan jika pengguna tersebut memiliki peran Admin: jika seorang Admin membuat draf pesanan, Admin lain harus menyetujuinya, atau sistem menolak *self-approval*.
3. **Database Constraint (Lapis Ketiga)**:
   - Pada tabel `sales_orders`, ditambahkan CHECK constraint sebagai benteng pertahanan terakhir:
     ```sql
     CONSTRAINT chk_so_sod CHECK (approved_by IS NULL OR approved_by != created_by)
     ```

## 3. Konsekuensi
### Positif:
- Keamanan berlapis (*defense-in-depth*): tidak ada celah bagi penyerang yang mem-bypass UI untuk melakukan approval ilegal.
- Integritas proses audit sesuai dengan standar compliance industri keuangan dan rantai pasok.
### Negatif:
- Memerlukan data seed demo dengan minimal 2 akun per peran (minimal 1 Admin dan 2 Sales) agar skenario pengujian SOD dapat didemonstrasikan secara nyata.
