# Class Diagram — Initial Architecture Design (DESIGN-01)

Dokumen ini merepresentasikan rancangan awal diagram kelas sebelum seluruh kode fitur pesanan diimplementasikan secara mendalam.

---

## 1. Initial Class Diagram (Mermaid)

```mermaid
classDiagram
    %% Layer 1: Controllers
    class AuthController {
        +login(input, ip)
        +logout()
    }
    class ProductController {
        +create(input, user)
        +update(input, user)
        +delete(input, user)
    }
    class OrderController {
        +createSalesOrder(input, user)
        +submitSalesOrder(input, user)
        +approveSalesOrder(input, user)
        +fulfillSalesOrder(input, user)
        +createPurchaseOrder(input, user)
        +receivePurchaseOrder(input, user)
    }

    %% Layer 2: Services
    class ProductService {
        +createProduct(input)
        +updateProduct(input)
        +deactivateProduct(id)
    }
    class OrderService {
        +createSalesOrder(user, input)
        +submitSalesOrder(user, orderId)
        +approveSalesOrder(user, orderId)
        +fulfillSalesOrder(user, orderId)
        +createPurchaseOrder(user, input)
        +receivePurchaseOrder(user, orderId)
    }

    %% Layer 3: Repositories
    class ProductRepositoryInterface {
        <<interface>>
        +findAll()
        +findById(id)
        +save(product)
    }
    class OrderRepositoryInterface {
        <<interface>>
        +findAllSalesOrders()
        +createSalesOrder(...)
        +updateSalesOrderStatus(...)
        +processGoodsIssue(...)
        +findAllPurchaseOrders(...)
        +createPurchaseOrder(...)
        +processGoodsReceipt(...)
    }

    class PdoProductRepository {
        -PDO pdo
        +findAll()
        +findById(id)
        +save(product)
    }
    class PdoOrderRepository {
        -PDO pdo
        +findAllSalesOrders()
        +createSalesOrder(...)
        +processGoodsIssue(...)
        +processGoodsReceipt(...)
    }

    %% Layer 4: Models / Entities
    class Product {
        +int id
        +string sku
        +string name
        +float purchasePrice
        +float sellingPrice
    }
    class SalesOrder {
        +int id
        +string soNumber
        +string status
        +float totalAmount
    }
    class PurchaseOrder {
        +int id
        +string poNumber
        +string status
        +float totalAmount
    }

    %% Relasi Awal
    ProductController ..> ProductService : uses
    OrderController ..> OrderService : uses
    ProductService ..> ProductRepositoryInterface : depends on
    OrderService ..> OrderRepositoryInterface : depends on
    PdoProductRepository ..|> ProductRepositoryInterface : implements
    PdoOrderRepository ..|> OrderRepositoryInterface : implements
    PdoProductRepository ..> Product : maps
    PdoOrderRepository ..> SalesOrder : maps
    PdoOrderRepository ..> PurchaseOrder : maps
```

---

## 2. Catatan Refleksi Awal
Pada rancangan awal, seluruh dependensi diarahkan pada abstraksi interface repository, dengan asumsi implementasi tunggal berbasis MySQL PDO. Penanganan pengujian unit belum memodelkan in-memory fake repository secara eksplisit pada diagram awal ini.
