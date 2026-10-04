# Refactoring Log & SRP Audit (DESIGN-03)

Dokumen ini mencatat riwayat pembersihan kode (*Boy Scout Rule*) dan pembongkaran kode berbau busuk (*Code Smells*) selama evolusi arsitektur sistem inventaris.

---

## 1. Audit Single Responsibility Principle (SRP)

### Kelas yang Diaudit: `Legacy DatabaseSeeder & Front Controller (public/index.php)`
- **Kondisi Awal (Melanggar SRP)**:
  Pada versi purwarupa awal, berkas `public/index.php` berukuran lebih dari 1.400 baris dan menjalankan tanggung jawab yang bertumpuk:
  1. Parsing HTTP request & Routing.
  2. Autentikasi sesi & pembatasan login (*Rate Limiting*).
  3. Query database mentah via PDO langsung di handler route.
  4. Perhitungan kuantitas stok dan validasi ketersediaan.
  5. Format output HTML template dan CSV stream.
- **Dampak Pelanggaran**:
  Setiap kali ada perubahan aturan bisnis (misal: perhitungan low-stock atau penambahan kolom), `public/index.php` harus diubah, meningkatkan risiko regresi bug dan mempersulit pengujian unit.
- **Tindakan Pemecahan (Refactoring)**:
  Tanggung jawab dipecah menjadi kelas-kelas terfokus:
  1. `App\Http\Session`: Khusus mengelola sesi PHP dan validasi CSRF token.
  2. `App\Infrastructure\LoginLimiter`: Khusus menangani rate-limiting percakapan login.
  3. `App\Repository\*`: Khusus menangani query SQL prepared statement.
  4. `App\Service\*`: Khusus menangani aturan validasi domain dan transaksi inventaris.
  5. `App\Controller\*`: Khusus mendelegasikan request ke service dan mengembalikan response JSON/HTML.
  Ukuran `public/index.php` berhasil dipangkas dari ~1.400 baris menjadi ~200 baris deklaratif yang bersih.

---

## 2. Refactoring Log (Minimal 3 Entri)

### Entri 1: God Method / Long Method -> Extract Method & Layering
- **Code Smell**: *Long Method* & *God Method* pada eksekusi penerimaan dan pengeluaran barang.
- **Masalah**: Satu blok kode besar melakukan validasi peran, pembacaan stok, penguncian baris, update status, dan pencatatan audit ledger secara tercampur aduk.
- **Teknik Refactoring**: *Extract Method* dan pemindahan tanggung jawab ke `OrderService` dan `PdoOrderRepository`.
- **Cuplikan Sebelum**:
  ```php
  // SEBELUM: Semua logic bercampur dalam 1 blok skrip procedural
  $stmt = $pdo->prepare("SELECT quantity_on_hand FROM inventory_stocks WHERE product_id = ?");
  $stmt->execute([$pId]);
  $stock = $stmt->fetchColumn();
  if ($stock < $requested) {
      die("Stok habis");
  }
  $pdo->query("UPDATE inventory_stocks SET quantity_on_hand = quantity_on_hand - $requested WHERE product_id = $pId");
  $pdo->query("INSERT INTO stock_ledger VALUES (...)");
  $pdo->query("UPDATE sales_orders SET status = 'fulfilled' WHERE id = $orderId");
  ```
- **Cuplikan Sesudah**:
  ```php
  // SESUDAH: Terisolasi rapi pada Service dan Repository transaksional
  // OrderService:
  public function fulfillSalesOrder(array $user, int $orderId): void {
      $this->assertWarehouseRole($user);
      $this->orderRepository->processGoodsIssue($orderId, (int) $user['id']);
  }

  // PdoOrderRepository:
  public function processGoodsIssue(int $salesOrderId, int $warehouseUserId): void {
      $this->pdo->beginTransaction();
      try {
          $this->lockAndValidateStock($salesOrderId);
          $this->deductStockAndRecordLedger($salesOrderId, $warehouseUserId);
          $this->updateSalesOrderStatus($salesOrderId, 'fulfilled');
          $this->pdo->commit();
      } catch (Throwable $e) {
          $this->pdo->rollBack();
          throw $e;
      }
  }
  ```

---

### Entri 2: Primitive Obsession & Data Clump -> Introduce Parameter Object / DTO Entity
- **Code Smell**: *Primitive Obsession* pada representasi pesanan dan mutasi inventaris.
- **Masalah**: Data pesanan dan stok dioper-oper antar fungsi menggunakan array asosiatif tanpa struktur tipe yang pasti (`$row['status']`, `$row['so_number']`), rentan terhadap typo key (`'so_no'` vs `'so_number'`).
- **Teknik Refactoring**: *Replace Data Value with Object* (Membuat model entitas `SalesOrder`, `PurchaseOrder`, dan `StockLedger` dengan properti strongly-typed PHP 8.2 `readonly`).
- **Cuplikan Sebelum**:
  ```php
  // SEBELUM: Mengembalikan associative array tak bertipe
  function getOrder($id) {
      return $pdo->query("SELECT * FROM sales_orders WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
  }
  // Di controller:
  $status = $order['status']; // Berisiko PHP warning jika key tidak ada
  ```
- **Cuplikan Sesudah**:
  ```php
  // SESUDAH: Strongly-typed Immutable Entity
  final readonly class SalesOrder {
      public function __construct(
          public int $id,
          public string $soNumber,
          public int $customerId,
          public int $warehouseId,
          public string $status,
          public float $totalAmount,
          public int $createdBy,
          public ?int $approvedBy = null,
          public ?string $notes = null,
          public ?string $createdAt = null,
          public ?string $customerName = null,
          public ?string $warehouseName = null,
          public ?string $creatorName = null,
          public ?string $approverName = null,
          public string $itemsSummary = 'Tidak ada item',
      ) {}
  }
  ```

---

### Entri 3: Divergent Change & Direct Database Coupling -> Dependency Inversion (DIP)
- **Code Smell**: *Divergent Change* & *Tight Coupling* terhadap koneksi database.
- **Masalah**: Modul logika bisnis langsung memanggil `Database::connect()` di dalam kelasnya. Akibatnya, pengujian unit tidak bisa dijalankan tanpa server database MySQL menyala.
- **Teknik Refactoring**: *Invert Dependencies* (Memperkenalkan `OrderRepositoryInterface` dan menginjeksikan repository via constructor `OrderService`).
- **Cuplikan Sebelum**:
  ```php
  // SEBELUM: Tightly coupled ke PDO nyata
  class OrderService {
      public function getProductAvailability(string $sku) {
          $pdo = Database::connect(); // Tersembunyi! Mustahil di-mock tanpa DB
          $stmt = $pdo->prepare("SELECT ...");
          ...
      }
  }
  ```
- **Cuplikan Sesudah**:
  ```php
  // SESUDAH: Bergantung pada Interface (DIP)
  class OrderService {
      public function __construct(
          private OrderRepositoryInterface $orderRepository
      ) {}

      public function getProductAvailability(string $sku): array {
          $data = $this->orderRepository->getProductAvailabilityBySku($sku);
          if ($data === null) {
              throw new NotFoundException("Produk tidak ditemukan.");
          }
          return $data;
      }
  }
  // Pada Unit Test: diinjeksi dengan InMemoryOrderRepository (Sub-millisecond test!)
  // Pada Production: diinjeksi dengan PdoOrderRepository
  ```
