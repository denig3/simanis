# Analisis Kritis Arsitektur Kode (DESIGN-04: Critique Exercise)

Dokumen ini berisi tinjauan dan kritik rekayasa perangkat lunak terhadap cuplikan kode yang sengaja dibuat bermasalah (*problematic anti-pattern code*) untuk menunjukkan pemahaman mendalam tentang prinsip Clean Code, SOLID, dan arsitektur berlapis.

---

## 1. Cuplikan Kode Bermasalah (Target Analisis)

```php
class OrderManagerService
{
    public function processOrder($data)
    {
        // 1. Validasi input
        if (empty($data['customer_email']) || !filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email tidak valid");
        }
        if (empty($data['items']) || count($data['items']) < 1) {
            throw new Exception("Item pesanan kosong");
        }

        // 2. Akses Database Langsung (Tight Coupling)
        $pdo = new PDO("mysql:host=localhost;dbname=inventory", "root", "");
        $pdo->beginTransaction();

        $total = 0;
        foreach ($data['items'] as $item) {
            $total += $item['qty'] * $item['price'];
            // Update stok langsung tanpa locking
            $pdo->query("UPDATE products SET stock = stock - " . (int)$item['qty'] . " WHERE id = " . (int)$item['id']);
        }

        $stmt = $pdo->prepare("INSERT INTO orders (email, total) VALUES (?, ?)");
        $stmt->execute([$data['customer_email'], $total]);
        $orderId = $pdo->lastInsertId();

        $pdo->commit();

        // 3. Pengiriman Notifikasi Email Langsung (Third-party side effect)
        $headers = "From: noreply@company.com\r\nContent-Type: text/html";
        $body = "<h1>Pesanan #{$orderId} Berhasil!</h1><p>Total: Rp " . number_format($total) . "</p>";
        mail($data['customer_email'], "Konfirmasi Pesanan", $body, $headers);

        return $orderId;
    }
}
```

---

## 2. Identifikasi Code Smells

1. **God Method / Long Method**:
   Metode `processOrder` memegang terlalu banyak tanggung jawab berbeda: memvalidasi format input, menginisialisasi koneksi basis data, menghitung kalkulasi harga, memperbarui stok inventaris, menyimpan pesanan, memformat template email, hingga mengirimkan email via protokol SMTP bawaan.
2. **Hidden Hardcoded Dependencies**:
   Instansiasi `new PDO(...)` dan pemanggilan `mail()` dibuat secara langsung (*hardcoded*) di dalam metode. Service tidak dapat diuji tanpa koneksi server database nyata dan server mail lokal.
3. **Race Condition & Missing Concurrency Control**:
   Query `UPDATE products SET stock = stock - ...` dieksekusi tanpa verifikasi apakah stok saat ini mencukupi (`SELECT ... FOR UPDATE`), sehingga dapat menghasilkan stok negatif (*oversell*).
4. **Primitive Obsession**:
   Parameter `$data` merupakan array asosiatif tanpa tipe (*untyped array*), rentan terhadap runtime error jika struktur data berubah.

---

## 3. Pelanggaran Prinsip SOLID

| Prinsip SOLID | Evaluasi Pelanggaran |
| :--- | :--- |
| **S — Single Responsibility Principle (SRP)** | **DILANGGAR KERAS**. Kelas memiliki lebih dari 3 alasan untuk berubah: perubahan aturan validasi email, perubahan struktur tabel database, atau pergantian penyedia layanan email (misal beralih dari `mail()` ke SendGrid/Mailgun). |
| **O — Open/Closed Principle (OCP)** | **DILANGGAR**. Jika sistem ingin menambahkan jenis notifikasi baru (misal notifikasi WhatsApp atau SMS), kode `processOrder` harus dimodifikasi secara langsung. |
| **D — Dependency Inversion Principle (DIP)** | **DILANGGAR KERAS**. Modul tingkat tinggi (`OrderManagerService`) bergantung langsung pada modul tingkat rendah konkret (`PDO` dan fungsi bawaan PHP `mail()`), bukan pada abstraksi interface. |

---

## 4. Arahan & Rekomendasi Refactoring

Untuk memperbaiki kode tersebut sesuai standar Clean Architecture:
1. **Pemisahan Boundary**:
   - Ekstrak kontrak akses data: `OrderRepositoryInterface` dan `StockRepositoryInterface`.
   - Ekstrak kontrak notifikasi: `NotificationServiceInterface` dengan implementasi `EmailNotificationService`.
2. **Terapkan Dependency Injection**:
   Injeksikan dependensi melalui constructor `OrderService`:
   ```php
   public function __construct(
       private OrderRepositoryInterface $orderRepo,
       private StockRepositoryInterface $stockRepo,
       private NotificationServiceInterface $notifier
   ) {}
   ```
3. **Amankan Transaksi & Stok (ACID + Pessimistic Locking)**:
   Gunakan `SELECT ... FOR UPDATE` sebelum memotong stok untuk menjamin tidak terjadi oversell jika dua transaksi berjalan bersamaan.
4. **Gunakan Typed DTO**:
   Bungkus data pesanan ke dalam kelas Strongly-Typed (misalnya `CreateOrderRequest`) dengan validasi otomatis.
5. **Pisahkan Side Effects**:
   Proses notifikasi email sebaiknya dijalankan setelah transaksi database sukses di-commit, atau dialihkan ke mekanisme queue agar kegagalan SMTP tidak membatalkan pesanan yang sah.
