# Architecture Decision Records (ADR)

Dokumen ini mencatat keputusan arsitektur utama yang diambil dalam pengembangan proyek **SIMANIS (Sistem Manajemen Inventaris & Order Management)**.

---

## Daftar ADR

- [ADR-001: Penggunaan PHP Native 8.2 dengan Arsitektur Berlapis (Layered Architecture)](#adr-001-penggunaan-php-native-82-dengan-arsitektur-berlapis-layered-architecture)
- [ADR-002: Penerapan Dependency Inversion Principle pada Boundary Repository](#adr-002-penerapan-dependency-inversion-principle-pada-boundary-repository)
- [ADR-003: Front Controller Pattern dan JSON API Endpoint dengan Vanilla JavaScript](#adr-003-front-controller-pattern-dan-json-api-endpoint-dengan-vanilla-javascript)
- [ADR-004: Kontainerisasi Menggunakan Docker Compose (PHP Apache + MySQL 8.0)](#adr-004-kontainerisasi-menggunakan-docker-compose-php-apache--mysql-80)
- [ADR-005: Strategi Keamanan (CSRF Protection, Rate Limiting, & Mitigasi Timing Attack)](#adr-005-strategi-keamanan-csrf-protection-rate-limiting--mitigasi-timing-attack)

---

## ADR-001: Penggunaan PHP Native 8.2 dengan Arsitektur Berlapis (Layered Architecture)

### Status
**Diterima (Accepted)**

### Konteks
Aplikasi membutuhkan fondasi backend yang ringan, transparan, dan mudah dipahami tanpa dependensi *overhead* dari framework besar (seperti Laravel atau Symfony), namun tetap mempertahankan kualitas kode tingkat industri dan kemudahan pemeliharaan (*maintainability*).

### Keputusan
Menggunakan **PHP 8.2 Native** dengan standar autoloading **PSR-4** (`App\` berpeta ke `src/`), serta menerapkan prinsip-prinsip *Domain-Driven Design (DDD)* / *Clean Architecture* sederhana yang membagi sistem menjadi 4 layer utama:
1. `src/Domain`: Berisi entitas (`User`), interface repository (`UserRepository`), dan exception domain.
2. `src/Application`: Berisi use-case / bisnis logika autentikasi (`Authenticator`, `RegisterUser`).
3. `src/Infrastructure`: Berisi akses database konkret (`PdoUserRepository`, `Database`, `LoginLimiter`).
4. `src/Http`: Berisi pengontrol HTTP (`AuthController`, `RegisterController`) dan manajemen sesi (`Session`).

### Konsekuensi
- **Positif:** Kode sangat modular, alur eksekusi jelas tanpa *magic* framework, performa tinggi, dan *footprint* memori minimal.
- **Negatif:** Fungsi dasar HTTP routing, validasi, dan penanganan respons JSON harus ditulis dan dikelola sendiri secara eksplisit.

---

## ADR-002: Penerapan Dependency Inversion Principle pada Boundary Repository

### Status
**Diterima (Accepted)**

### Konteks
Service pada layer aplikasi (`Authenticator` dan `RegisterUser`) membutuhkan akses penyimpanan data pengguna. Mengikat secara langsung ke kelas konkrit PDO (`PdoUserRepository`) akan membuat layer aplikasi bergantung pada infrastruktur database, sehingga sulit diuji dengan unit test (mocking).

### Keputusan
Menerapkan **Dependency Inversion Principle (DIP)**:
- Interface `UserRepository` didefinisikan di dalam layer `src/Domain/`.
- Kelas konkrit `PdoUserRepository` di dalam layer `src/Infrastructure/` mengimplementasikan interface `UserRepository`.
- Layer aplikasi (`Authenticator` & `RegisterUser`) hanya menerima interface `UserRepository` melalui *constructor injection*.

```php
// Application Layer hanya tergantung pada abstraksi interface
final class Authenticator
{
    public function __construct(private UserRepository $users) {}
}
```

### Konsekuensi
- **Positif:** Isolasi sempurna antara bisnis logika dan database. Unit testing dapat dilakukan dengan cepat menggunakan *mock/stub repository* tanpa koneksi database aktual.
- **Negatif:** Memerlukan pembuatan file interface terpisah dan penginstansian objek secara manual di Front Controller.

---

## ADR-003: Front Controller Pattern dan JSON API Endpoint dengan Vanilla JavaScript

### Status
**Diterima (Accepted)**

### Konteks
Aplikasi membutuhkan interaksi yang responsif (*smooth UX*) tanpa *page reload* penuh untuk proses autentikasi (login/register/logout), namun tetap menjaga kesederhanaan frontend tanpa *build step* atau framework JavaScript rumit.

### Keputusan
1. **Front Controller (`public/index.php`):** Seluruh permintaan diarahkan melalui Apache ke `index.php`.
2. **JSON API Endpoint:** Jalur `/api/login`, `/api/register`, dan `/api/logout` memproses permintaan berformat JSON dan mengembalikan respons HTTP JSON secara eksplisit.
3. **Vanilla JavaScript (`public/assets/app.js`):** Menggunakan Fetch API native, `FormData`, dan manipulasi DOM modern untuk berinteraksi dengan endpoint JSON API.

### Konsekuensi
- **Positif:** Tidak ada dependensi node_modules / build tool (npm/vite) untuk frontend. Performa rendering cepat dan efisien.
- **Negatif:** Pengelolaan state UI di sisi frontend dilakukan secara manual via DOM API.

---

## ADR-004: Kontainerisasi Menggunakan Docker Compose (PHP Apache + MySQL 8.0)

### Status
**Diterima (Accepted)**

### Konteks
Memastikan lingkungan eksekusi (PHP version, modul PDO, Apache rewrite, MySQL version) konsisten antara lingkungan pengembangan lokal dan pengujian.

### Keputusan
Menggunakan **Docker Compose** (`compose.yaml`) dengan dua layanan terisolasi:
1. `app`: Menggunakan `Dockerfile` berbasis image resmi `php:8.2-apache`, dikonfigurasi dengan module `pdo_mysql`, custom `apache.conf`, dan `php.ini`.
2. `db`: Menggunakan image `mysql:8.0` dengan pengujian kondisi *healthcheck* otomatis dan inisialisasi skema awal melalui `database/schema.sql`.

### Konsekuensi
- **Positif:** Aplikasi dapat dijalankan di mana saja hanya dengan perintah `docker compose up -d` tanpa perlu instalasi PHP/MySQL lokal secara manual.
- **Negatif:** Membutuhkan Docker Desktop aktif pada mesin pengembang.

---

## ADR-005: Strategi Keamanan (CSRF Protection, Rate Limiting, & Mitigasi Timing Attack)

### Status
**Diterima (Accepted)**

### Konteks
Aplikasi autentikasi rentan terhadap berbagai jenis serangan web seperti Cross-Site Request Forgery (CSRF), Brute-Force Password Guessing, dan Timing Attack pada pencarian akun.

### Keputusan
1. **CSRF Protection:** Setiap sesi dibekali token acak 64-karakter hex. Endpoint API wajib menyertakan header `X-CSRF-Token` yang divalidasi oleh `Session::validToken()`.
2. **Rate Limiting:** Menggunakan kelas `LoginLimiter` berbasis tabel MySQL `login_attempts` dengan *sliding window 15 menit* (maksimal 30 percobaan per IP, 5 per email, 10 per registrasi).
3. **Mitigasi Timing Attack:** Di `Authenticator.php`, jika email tidak ditemukan di database, sistem tetap melakukan `password_verify()` menggunakan `DUMMY_HASH` agar waktu komputasi respons sama persis antara akun yang ada dan tidak ada.
4. **Security Headers & Cookies:** Cookie sesi dikonfigurasi dengan `HttpOnly`, `SameSite=Lax`, serta header CSP (`Content-Security-Policy`), `X-Content-Type-Options: nosniff`, dan `Cache-Control: no-store`.

### Konsekuensi
- **Positif:** Keamanan aplikasi berada pada level tinggi untuk proteksi akun dan pencegahan eksploitasi umum OWASP.
- **Negatif:** Terdapat sedikit penambahan overhead query database untuk pencatatan *rate limit*.
