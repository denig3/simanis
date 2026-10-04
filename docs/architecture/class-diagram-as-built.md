# Class Diagram — As-Built Final Architecture (DESIGN-01)

Dokumen ini merepresentasikan arsitektur final aktual (*as-built*) yang telah terimplementasi dan terverifikasi di dalam basis kode, membedakan secara tegas antara dependensi interface dan kelas konkret.

---

## 1. As-Built Class Diagram (Mermaid)

```mermaid
classDiagram
    %% Layer 1: HTTP Presentation Controllers (Concrete)
    class OrderController {
        -OrderService orderService
        +createSalesOrder(input, user) void
        +submitSalesOrder(input, user) void
        +approveSalesOrder(input, user) void
        +rejectSalesOrder(input, user) void
        +fulfillSalesOrder(input, user) void
        +cancelSalesOrder(input, user) void
        +createPurchaseOrder(input, user) void
        +orderPurchaseOrder(input, user) void
        +receivePurchaseOrder(input, user) void
        +getAvailability(sku, user) void
    }

    %% Layer 2: Core Domain Services (Concrete)
    class OrderService {
        -OrderRepositoryInterface orderRepository
        +createSalesOrder(user, input) int
        +submitSalesOrder(user, orderId) void
        +approveSalesOrder(user, orderId) void
        +rejectSalesOrder(user, orderId) void
        +fulfillSalesOrder(user, orderId) void
        +createPurchaseOrder(user, input) int
        +orderPurchaseOrder(user, orderId) void
        +receivePurchaseOrder(user, orderId, items) void
        +getProductAvailability(sku) array
    }

    %% Layer 3: Repository Interfaces (Contract / Boundary)
    class OrderRepositoryInterface {
        <<interface>>
        +findAllSalesOrders() list~SalesOrder~
        +findSalesOrderById(id) SalesOrder
        +createSalesOrder(custId, whId, userId, notes, items) int
        +updateSalesOrderStatus(orderId, status, approverId) bool
        +processGoodsIssue(orderId, userId) void
        +findAllPurchaseOrders() list~PurchaseOrder~
        +findPurchaseOrderById(id) PurchaseOrder
        +createPurchaseOrder(supId, whId, userId, notes, items) int
        +updatePurchaseOrderStatus(orderId, status, approverId) bool
        +processGoodsReceipt(orderId, userId, items) void
        +findAllStockLedger() list~StockLedger~
        +getProductAvailabilityBySku(sku) array
    }

    %% Layer 4A: Production Concrete Repository (MySQL 8)
    class PdoOrderRepository {
        -PDO pdo
        +findAllSalesOrders() list~SalesOrder~
        +processGoodsIssue(orderId, userId) void
        +processGoodsReceipt(orderId, userId, items) void
        +getProductAvailabilityBySku(sku) array
    }

    %% Layer 4B: Testing Concrete Repository (In-Memory Fake)
    class InMemoryOrderRepository {
        +array salesOrders
        +array purchaseOrders
        +array stocks
        +array stockLedger
        +processGoodsIssue(orderId, userId) void
        +processGoodsReceipt(orderId, userId, items) void
        +getProductAvailabilityBySku(sku) array
    }

    %% Domain Entities / Immutable Models
    class SalesOrder {
        +int id
        +string soNumber
        +int customerId
        +int warehouseId
        +string status
        +float totalAmount
        +int createdBy
        +int approvedBy
        +toArray() array
    }

    class PurchaseOrder {
        +int id
        +string poNumber
        +int supplierId
        +int warehouseId
        +string status
        +float totalAmount
        +int createdBy
        +int approvedBy
        +toArray() array
    }

    class StockLedger {
        +int id
        +int warehouseId
        +int productId
        +string movementType
        +int quantityDelta
        +int balanceAfter
        +string referenceDocType
        +string referenceDocNumber
        +int userId
        +toArray() array
    }

    %% Relasi & Dependensi
    OrderController --> OrderService : depends on (Concrete Class)
    OrderService ..> OrderRepositoryInterface : depends on (Interface Boundary - DIP)
    
    PdoOrderRepository ..|> OrderRepositoryInterface : implements (Production MySQL)
    InMemoryOrderRepository ..|> OrderRepositoryInterface : implements (Testing Fake)

    PdoOrderRepository ..> SalesOrder : instantiates
    PdoOrderRepository ..> PurchaseOrder : instantiates
    PdoOrderRepository ..> StockLedger : instantiates

    InMemoryOrderRepository ..> SalesOrder : instantiates
    InMemoryOrderRepository ..> PurchaseOrder : instantiates
    InMemoryOrderRepository ..> StockLedger : instantiates
```

---

## 2. Analisis Perubahan: Initial vs As-Built

> **Perubahan Nyata & Alasan Desain**:
> 1. **Penambahan `InMemoryOrderRepository`**: Pada diagram initial hanya terdapat `PdoOrderRepository`. Pada as-built ditambahkan `InMemoryOrderRepository` yang mengimplementasikan `OrderRepositoryInterface` secara penuh, agar pengujian unit `OrderServiceTest` (TEST-01) dapat berjalan 100% terisolasi tanpa menyentuh koneksi database nyata, sesuai prinsip Dependency Inversion (ARCH-01).
> 2. **Pemisahan Metode Concurrency-Safe**: Pada interface dan implementasi ditambahkan tanda tangan metode eksplisit `processGoodsIssue` dan `processGoodsReceipt` yang membungkus Pessimistic Locking (`SELECT ... FOR UPDATE`) dan penulisan atomik `StockLedger` (ARCH-02), bukan sekadar update status biasa.
> 3. **Spesifikasi Kontrak Endpoint API**: Ditambahkan method `getProductAvailabilityBySku` pada boundary repository untuk mendukung kontrak endpoint JSON `GET /api/products/{sku}/availability` (API-01).
