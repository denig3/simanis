# ADR 001: Penggunaan Repository Pattern dan Dependency Inversion

- **Status**: Accepted
- **Tanggal**: Oktober 2026
- **Konteks**: Intermediate Programmer Final Project (ARCH-01)

---

## 1. Konteks & Masalah
Dalam aplikasi PHP native tanpa framework, kecenderungan umum yang sering terjadi adalah mencampur query SQL (PDO) langsung di dalam file Controller atau view (Active Record / Procedural Scripting). Hal ini menimbulkan masalah:
- Logika bisnis terikat erat (*tightly coupled*) dengan implementasi fisik database.
- Controller menjadi sangat gemuk (*Fat Controller* / God Object).
- Pengujian unit otomatis (*unit testing*) menjadi mustahil dilakukan tanpa adanya koneksi database MySQL aktif yang berjalan, memperlambat siklus CI/CD.

## 2. Keputusan Arsitektur
Kami memutuskan untuk menerapkan **Clean Layered Architecture (Controller -> Service -> Repository -> Model)** dengan prinsip **Dependency Inversion Principle (DIP)**:
1. `Controller` hanya bertugas menerima HTTP request, validasi format, dan mengembalikan HTTP response.
2. `Service` memegang seluruh aturan bisnis domain dan validasi alur.
3. Akses data diisolasi di balik kontrak `RepositoryInterface`. `Service` hanya bergantung pada interface tersebut via Constructor Injection (`__construct(private OrderRepositoryInterface $repo)`).
4. Disediakan dua implementasi konkret:
   - `PdoOrderRepository` untuk lingkungan produksi dengan koneksi MySQL 8 PDO.
   - `InMemoryOrderRepository` untuk lingkungan pengujian unit terisolasi (Zero DB).

## 3. Konsekuensi
### Positif:
- **Testability Tinggi**: Logika validasi status, aturan SOD, dan pengecekan kuantitas dapat diuji dalam hitungan milidetik menggunakan fake in-memory repository tanpa perlu setup database.
- **Maintainability & Clean Code**: Query database terpusat di repository dengan prepared statements yang aman dari SQL Injection.
- **Kepatuhan Terhadap Standar Industri**: Memenuhi standar arsitektur modular tanpa ketergantungan pada framework eksternal yang dilarang.

### Negatif / Biaya:
- Menambah jumlah file interface dan class boilerplate (harus menulis interface dan dua implementasi). Namun ini adalah investasi terukur yang sangat layak untuk integritas dan stabilitas sistem.
