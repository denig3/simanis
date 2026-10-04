# Panduan Akses Dashboard & Database SIMANIS (Sistem Manajemen Inventaris)

Dokumen ini memuat informasi lengkap mengenai kredensial dan cara mengakses **Aplikasi Web / Dashboard SIMANIS** serta **Koneksi Database MySQL** (menggunakan DBeaver, GUI database, atau terminal).

---

## 1. Akses Aplikasi Web & Dashboard

### 1.1 URL Aplikasi
* **URL Utama**: [http://localhost:8080](http://localhost:8080)
* **Halaman Login**: [http://localhost:8080/login](http://localhost:8080/login)
* **Halaman Registrasi Akun Baru**: [http://localhost:8080/register](http://localhost:8080/register)

### 1.2 Akun Login Default
Gunakan akun berikut untuk masuk ke dalam sistem:

| Parameter | Kredensial |
|---|---|
| **Email** | `admin@example.com` |
| **Password** | `password123` |
| **Role Bawaan** | Administrator |

> **Catatan:** Anda juga dapat mendaftarkan akun baru melalui halaman `/register`. Password minimal 12 karakter sesuai standar keamanan aplikasi.

---

### 1.3 Penggunaan Role Switcher & SOD (Segregation of Duties) di Dashboard
Setelah berhasil login, di bagian atas (*topbar*) tersedia fitur **Simulator Peran** untuk menguji aturan Segregation of Duties secara langsung:

1. **Admin System (Ungu):**
   * Memiliki akses menu: Ringkasan Dashboard Lengkap, Manajemen User, Master Data & Gudang (CRUD penuh), Sales Orders, Purchase Orders, dan Stock Ledger Multigudang.
   * Berhak menyetujui (*Approve*) atau menolak (*Reject*) Sales Order.
2. **Sales Staff (Biru):**
   * Ringkasan dashboard otomatis berganti fokus ke performa pesanan penjualan miliknya.
   * Menu Master Data berubah menjadi **Katalog Produk** (*read-only* daftar harga dan stok).
   * Tab **Purchase Orders disembunyikan** dari sidebar (Sales dilarang mengelola pengadaan).
   * Dilarang menyetujui Sales Order miliknya sendiri (Prinsip SOD).
3. **Staff Gudang / Warehouse (Kuning):**
   * Ringkasan dashboard otomatis berganti fokus ke antrean pengiriman (*Goods Issue*) dan penerimaan barang (*Goods Receipt*).
   * Menu Master Data berubah menjadi **Produk & Stok Gudang** (*read-only* ketersediaan fisik per lokasi gudang).
   * Tombol *"Buat SO Baru"* otomatis disembunyikan (Staf gudang tidak berwenang menjual).
   * Tombol PO berubah menjadi *"Usulkan Purchase Order (PO)"*.
   * Dapat mengeksekusi Goods Issue dan Goods Receipt yang langsung memicu pencatatan audit trail pada **Stock Ledger**.

---

## 2. Akses Koneksi Database (DBeaver / GUI Database)

Database berjalan di dalam container Docker dan port `3306` telah dipetakan ke localhost host komputer Anda.

### 2.1 Parameter Koneksi DBeaver / Navicat / TablePlus

| Parameter | Nilai Konfigurasi User Biasa | Nilai Konfigurasi Root |
|---|---|---|
| **Driver / Tipe Database** | **MySQL** (atau **MySQL 8+**) | **MySQL** |
| **Host / Server** | `127.0.0.1` atau `localhost` | `127.0.0.1` atau `localhost` |
| **Port** | `3306` | `3306` |
| **Database Name** | `inventory` | `inventory` |
| **Username** | `inventory` | `root` |
| **Password** | `change-this-local-db-password` | `change-this-local-root-password` |

### 2.2 Langkah Koneksi pada DBeaver
1. Buka DBeaver, klik menu **Database** ➔ **New Database Connection**.
2. Pilih **MySQL** lalu klik **Next**.
3. Pada tab **Main**, masukkan Host `127.0.0.1`, Port `3306`, Database `inventory`, Username `inventory`, dan Password `change-this-local-db-password`.
4. Buka tab **Driver properties**:
   * Set `allowPublicKeyRetrieval` = **`TRUE`**
   * Set `useSSL` = **`FALSE`**
5. Klik **Test Connection...**. Jika diminta mengunduh driver MySQL JDBC, klik **Download**.
6. Klik **Finish**.

---

### 2.3 Akses Database via Terminal / Docker CLI
Jika ingin mengakses MySQL langsung melalui command line PowerShell / CMD:

```powershell
# Masuk sebagai user aplikasi (inventory):
docker compose exec -it db mysql -u inventory -pchange-this-local-db-password inventory

# Atau masuk sebagai user root:
docker compose exec -it db mysql -u root -pchange-this-local-root-password inventory
```

---

## 3. Daftar Tabel Database yang Tersedia

Terdapat **17 tabel** relasional aktif di dalam database `inventory`:
* **Master Data**: `warehouses`, `categories`, `products`, `customers`, `suppliers`
* **Keamanan & Pengguna**: `users`, `login_attempts`
* **Modul Penjualan**: `sales_orders`, `sales_order_items`, `goods_issues`, `goods_issue_items`
* **Modul Pengadaan**: `purchase_orders`, `purchase_order_items`, `goods_receipts`, `goods_receipt_items`
* **Stok & Audit Trail**: `inventory_stocks`, `stock_ledger`

---

## 4. Perintah Manajemen Lingkungan Docker

Jalankan perintah ini di direktori proyek `d:\Learn\Web`:

```powershell
# Menjalankan seluruh kontainer aplikasi dan database
docker compose up -d

# Memeriksa status kontainer dan port yang aktif
docker compose ps

# Melihat log aplikasi jika terjadi kendala
docker compose logs -f

# Menjalankan Automated Unit Tests (PHPUnit)
docker compose exec app vendor/bin/phpunit

# Menjalankan Analisis Statis Kode (PHPStan)
docker compose exec app vendor/bin/phpstan analyse
```
