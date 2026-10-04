# Entity Relationship Diagram (ERD) — STOKORA

Dokumen ini memetakan relasi data, kunci primer/asing, dan batasan integritas (*integrity constraints*) sistem inventaris dan pesanan multi-gudang.

---

## 1. Diagram ERD (Mermaid)

```mermaid
erDiagram
    users ||--o{ sales_orders : "creates (created_by)"
    users ||--o{ sales_orders : "approves (approved_by)"
    users ||--o{ purchase_orders : "creates (created_by)"
    users ||--o{ purchase_orders : "approves (approved_by)"
    users ||--o{ stock_ledger : "recorded_by"
    users ||--o{ goods_issues : "issued_by"
    users ||--o{ goods_receipts : "received_by"

    warehouses ||--o{ inventory_stocks : "holds"
    warehouses ||--o{ sales_orders : "originates_from"
    warehouses ||--o{ purchase_orders : "delivered_to"
    warehouses ||--o{ stock_ledger : "tracked_at"

    categories ||--o{ products : "classifies"

    products ||--o{ inventory_stocks : "stocked_as"
    products ||--o{ sales_order_items : "contained_in"
    products ||--o{ purchase_order_items : "contained_in"
    products ||--o{ stock_ledger : "mutated_in"

    customers ||--o{ sales_orders : "places"
    suppliers ||--o{ purchase_orders : "supplies"

    sales_orders ||--|{ sales_order_items : "has"
    sales_orders ||--o| goods_issues : "fulfilled_by"
    goods_issues ||--|{ goods_issue_items : "contains"

    purchase_orders ||--|{ purchase_order_items : "has"
    purchase_orders ||--o{ goods_receipts : "fulfilled_by"
    goods_receipts ||--|{ goods_receipt_items : "contains"

    users {
        bigint id PK
        varchar name
        varchar email UK
        varchar password_hash
        enum role "admin, sales, warehouse"
        bigint assigned_warehouse_id FK
        enum status "active, inactive"
    }

    warehouses {
        bigint id PK
        varchar code UK
        varchar name
        varchar city
        tinyint is_active
    }

    categories {
        bigint id PK
        varchar code UK
        varchar name
        text description
    }

    products {
        bigint id PK
        varchar sku UK
        varchar name
        bigint category_id FK
        varchar unit
        decimal purchase_price
        decimal selling_price
        int min_stock_threshold
        varchar image_path
        tinyint is_active
    }

    inventory_stocks {
        bigint id PK
        bigint warehouse_id FK
        bigint product_id FK
        int quantity_on_hand "CHECK >= 0"
        int quantity_reserved
        int quantity_incoming
    }

    stock_ledger {
        bigint id PK
        bigint warehouse_id FK
        bigint product_id FK
        enum movement_type "GOODS_RECEIPT, GOODS_ISSUE, ADJUSTMENT"
        int quantity_delta
        int balance_after
        enum reference_doc_type "PO, SO"
        varchar reference_doc_number
        bigint user_id FK
        timestamp created_at
    }

    sales_orders {
        bigint id PK
        varchar so_number UK
        bigint customer_id FK
        bigint warehouse_id FK
        enum status "draft, pending_approval, approved, fulfilled, cancelled"
        decimal total_amount
        bigint created_by FK
        bigint approved_by FK
    }

    purchase_orders {
        bigint id PK
        varchar po_number UK
        bigint supplier_id FK
        bigint warehouse_id FK
        enum status "draft, sent_to_supplier, goods_received, cancelled"
        decimal total_amount
        bigint created_by FK
        bigint approved_by FK
    }
```

---

## 2. Aturan Integritas Data Relasional (DB-01 & ARCH-02)

1. **Foreign Key Integrity**:
   - Seluruh relasi transaksi (`sales_orders`, `purchase_orders`, `stock_ledger`) memiliki Foreign Key eksplisit ke tabel master.
   - Penghapusan data master (*hard delete*) pada data yang memiliki relasi transaksi dilarang keras via constraint database. Status diganti melalui `is_active = 0` (*soft-deactivation*).
2. **Kekangan Non-Negatif (Check Constraint)**:
   - `inventory_stocks` memiliki constraint `chk_stk_qty_positive CHECK (quantity_on_hand >= 0)`. Hal ini menjamin bahwa database engine InnoDB akan menolak update apa pun yang menghasilkan stok minus.
3. **Audit Trail Mutasi Stok**:
   - Setiap mutasi saldo di `inventory_stocks` memiliki korespondensi 1-ke-1 dengan baris di `stock_ledger` yang dieksekusi dalam transaksi atomik yang sama.
