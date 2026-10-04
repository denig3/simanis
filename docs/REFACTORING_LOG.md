# Refactoring Log

Log ini mencatat riwayat evolusi kode dan langkah-langkah refactoring yang dilakukan pada proyek **SIMANIS (Sistem Manajemen Inventaris & Order)** dari bentuk awal hingga mencapai arsitektur bersih (*Clean Architecture*) saat ini.

---

## Log Riwayat Refactoring

### [Iterasi 1] - Pemisahan Layer Domain dan Infrastruktur (Repository Pattern)
- **Problem / Technical Debt:** Logic akses database SQL sebelumnya bercampur langsung dengan pengolahan input HTTP, membuat kode sulit diuji dan terikat erat pada driver MySQL.
- **Refactoring Steps:**
  1. Membuat interface `App\Domain\UserRepository` di layer Domain sebagai kontrak utama manipulasi data pengguna (`findByEmail` dan `create`).
  2. Membuat implementasi konkrit `App\Infrastructure\PdoUserRepository` yang membungkus prepared statement PDO MySQL.
  3. Memindahkan penanganan error duplikasi email MySQL (`errorInfo[1] === 1062`) menjadi domain-specific exception `App\Domain\EmailAlreadyRegistered`.
- **Hasil:** Penanganan query SQL terisolasi sepenuhnya di layer infrastruktur.

---

### [Iterasi 2] - Ekstraksi Business Logic / Use Case (Application Layer)
- **Problem / Technical Debt:** Aturan bisnis autentikasi (verifikasi hash password, normalisasi email) dan validasi registrasi (panjang password, konfirmasi password, format email) berada di skrip controller.
- **Refactoring Steps:**
  1. Mengorientasikan use-case pendaftaran ke dalam kelas `App\Application\RegisterUser`.
  2. Mengorientasikan use-case autentikasi ke dalam kelas `App\Application\Authenticator`.
  3. Menerapkan *Dependency Inversion* pada `Authenticator` dan `RegisterUser` agar hanya meminta `UserRepository` interface.
  4. Menambahkan algoritma *Dummy Hash* (`password_verify` terhadap hash statis saat akun tidak ditemukan) di `Authenticator` untuk mencegah serangan *timing attack*.
- **Hasil:** Logika bisnis murni dapat diuji secara terisolasi menggunakan Unit Test tanpa database.

---

### [Iterasi 3] - Refactoring HTTP Layer dan Manajer Sesi
- **Problem / Technical Debt:** Pengelolaan sesi PHP bawaan (`$_SESSION`) dan pembentukan respons JSON sebelumnya tersebar di berbagai file.
- **Refactoring Steps:**
  1. Membentuk kelas helper statis `App\Http\Session` untuk mengenkapsulasi pengisian cookie sesi (`HttpOnly`, `SameSite`), regenerasi ID sesi (`session_regenerate_id`), dan manajemen token CSRF.
  2. Membentuk `App\Http\AuthController` dan `App\Http\RegisterController` untuk menangani pembacaan array input, koordinasi dengan service layer, serta pembentukan respons status HTTP JSON.
  3. Menambahkan method utilitas `AuthController::json()` untuk menyelaraskan format header `Content-Type: application/json` dan penanganan HTTP status code (200, 201, 400, 401, 403, 409, 429, 503).
- **Hasil:** Respons API konsisten dan penanganan sesi terpusat secara aman.

---

### [Iterasi 4] - Peningkatan Keamanan & Rate Limiting Berbasis Database
- **Problem / Technical Debt:** Sistem rentan terhadap *brute force login* dan serangan *CSRF forgery*.
- **Refactoring Steps:**
  1. Membuat kelas `App\Infrastructure\LoginLimiter` yang memanfaatkan tabel database `login_attempts` untuk mencatat percobaan berbasis jendela waktu 15 menit (*sliding window*).
  2. Menambahkan pembatasan *rate limit* spesifik: maks 30 percobaan per IP, 5 per email, dan 10 per alamat registrasi.
  3. Mengintegrasikan header HTTP `X-CSRF-Token` pada seluruh permintaan API POST yang diperiksa oleh Front Controller `public/index.php`.
- **Hasil:** Aplikasi terlindungi dari serangan peretasan kredensial dan manipulasi form dari situs asing.

---

### [Iterasi 5] - Peningkatan Pengujian (Unit Test & Integration Smoke Test)
- **Problem / Technical Debt:** Pengujian manual memakan waktu dan berisiko menimbulkan regresi pada fitur autentikasi.
- **Refactoring Steps:**
  1. Memasang PHPUnit 11 dan merancang unit test di [`tests/AuthenticatorTest.php`](file:///d:/Learn/Web/tests/AuthenticatorTest.php) menggunakan PHPUnit Mocking.
  2. Merancang unit test CSRF di [`tests/CsrfTest.php`](file:///d:/Learn/Web/tests/CsrfTest.php).
  3. Membuat skrip otomasi pengujian integrasi HTTP & Database end-to-end pada [`bin/smoke-test.php`](file:///d:/Learn/Web/bin/smoke-test.php) yang menguji seluruh alur pendaftaran, login, rate limiting, sanitasi data, hingga logout.
- **Hasil:** Semua fungsionalitas utama dapat divalidasi secara otomatis melalui perintah `composer check` dan `php bin/smoke-test.php`.
