# Class Diagram & Architecture Overview

Dokumen ini memuat **Class Diagram** utama proyek **Stokora (Inventory & Order Management)** beserta penjelasan rinci hubungan antar-komponen pada setiap *layer* arsitektur.

---

## Class Diagram (Mermaid)

Berikut adalah diagram kelas UML yang menggambarkan struktur OOP, batas antarlayer (*boundary*), dan penerapan *Dependency Inversion Principle*:

```mermaid
classDiagram
    %% Layer Domain
    namespace Domain {
        class UserRepository {
            <<interface>>
            +findByEmail(string email) User~nullable~
            +create(string name, string email, string passwordHash) void
        }
        class User {
            +int id
            +string name
            +string email
            +string passwordHash
            +__construct(int id, string name, string email, string passwordHash)
        }
        class EmailAlreadyRegistered {
            <<exception>>
        }
    }

    %% Layer Application
    namespace Application {
        class Authenticator {
            -UserRepository users
            -string DUMMY_HASH
            +__construct(UserRepository users)
            +attempt(string email, string password) User~nullable~
        }
        class RegisterUser {
            -UserRepository users
            +__construct(UserRepository users)
            +register(string name, string email, string password, string confirmation) void
        }
    }

    %% Layer Infrastructure
    namespace Infrastructure {
        class PdoUserRepository {
            -PDO pdo
            +__construct(PDO pdo)
            +findByEmail(string email) User~nullable~
            +create(string name, string email, string passwordHash) void
        }
        class Database {
            +connect()* PDO
        }
        class LoginLimiter {
            -PDO pdo
            +__construct(PDO pdo)
            +consume(string bucket, int maxAttempts) bool
        }
    }

    %% Layer Http
    namespace Http {
        class AuthController {
            -Authenticator auth
            -LoginLimiter limiter
            +__construct(Authenticator auth, LoginLimiter limiter)
            +login(array input, string ip) never
            +json(array data, int status)* never
        }
        class RegisterController {
            -RegisterUser registration
            -LoginLimiter limiter
            +__construct(RegisterUser registration, LoginLimiter limiter)
            +register(array input, string ip) never
        }
        class Session {
            +start()* void
            +login(User user)* void
            +logout()* void
            +user()* User~nullable~
            +token()* string
            +validToken(string token)* bool
        }
    }

    %% Realization (Implementation)
    PdoUserRepository ..|> UserRepository : implements

    %% Dependencies & Association (Dependency Inversion)
    Authenticator --> UserRepository : depends on abstraction
    RegisterUser --> UserRepository : depends on abstraction
    
    %% Controller Dependencies
    AuthController --> Authenticator : uses
    AuthController --> LoginLimiter : uses
    RegisterController --> RegisterUser : uses
    RegisterController --> LoginLimiter : uses
    
    %% Infrastructure Dependencies
    PdoUserRepository --> Database : uses connection
    LoginLimiter --> Database : uses connection
    PdoUserRepository ..> User : instantiates / returns
    PdoUserRepository ..> EmailAlreadyRegistered : throws on duplicate
```

---

## Penjelasan Komponen Per Layer

### 1. Domain Layer (`src/Domain`)
- **`UserRepository` (Interface):** Kontrak abstraksi untuk penyimpanan dan pengambilan data pengguna. Komponen pada layer aplikasi hanya bergantung pada interface ini.
- **`User` (Entity Class):** Objek nilai (*Value Object* / *Read-only Entity*) yang merepresentasikan data akun pengguna terautentikasi (ID, Nama, Email, Password Hash).
- **`EmailAlreadyRegistered` (Exception):** Exception khusus domain yang dilempar saat terjadi pendaftaran dengan email yang sudah ada.

### 2. Application Layer (`src/Application`)
- **`Authenticator` (Service Class):** Mengatur logika autentikasi pengguna (pencarian email, normalisasi string, dan mitigasi *timing attack* via dummy hash verification).
- **`RegisterUser` (Service Class):** Mengatur logika registrasi akun baru (validasi panjang password 12-72 byte, konfirmasi password, validasi format email, dan penyiapan password hash).

### 3. Infrastructure Layer (`src/Infrastructure`)
- **`PdoUserRepository` (Repository Implementation):** Implementasi konkrit `UserRepository` berbasis PDO MySQL. Menggunakan *prepared statements* untuk mencegah SQL Injection.
- **`Database` (Utility Class):** Penyedia koneksi PDO singleton ke MySQL berdasarkan variabel lingkungan `.env`.
- **`LoginLimiter` (Security Component):** Pengelola *rate-limiting* berbasis tabel database `login_attempts` untuk mencegah *brute-force attack*.

### 4. Http Layer (`src/Http`)
- **`AuthController` (Controller Class):** Membaca input JSON login, memeriksa rate limit, memanggil `Authenticator`, serta mengatur sesi pengguna & respons JSON.
- **`RegisterController` (Controller Class):** Membaca input JSON registrasi, memanggil `RegisterUser`, serta menangani penangkapan exception domain untuk respons status HTTP 409 / 422.
- **`Session` (Session Helper Class):** Pengelola *session cookie* (HttpOnly, SameSite), pembaruan ID sesi (*session fixation prevention*), dan pembentukan/validasi token CSRF.
