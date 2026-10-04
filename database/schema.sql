-- ==============================================================================
-- SCHEMA & SEED DATABASE SISTEM INVENTARIS & ORDER MULTIGUDANG (SIMANIS - SISTEM MANAJEMEN INVENTARIS)
-- Sesuai Spesifikasi Final Project Intermediate Programmer PT Neuronworks Indonesia
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. PENGGUNA & KONTROL AKSES (SOD MATRIX)
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'sales', 'warehouse') NOT NULL DEFAULT 'sales',
    assigned_warehouse_id BIGINT UNSIGNED NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_role (role),
    INDEX idx_user_wh (assigned_warehouse_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    bucket CHAR(64) PRIMARY KEY,
    attempts INT UNSIGNED NOT NULL DEFAULT 1,
    window_start BIGINT NOT NULL,
    INDEX idx_window_start (window_start)
) ENGINE=InnoDB;

-- 2. MASTER DATA MULTIGUDANG
CREATE TABLE IF NOT EXISTS warehouses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    address TEXT NULL,
    city VARCHAR(50) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    unit VARCHAR(20) NOT NULL DEFAULT 'unit',
    purchase_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    selling_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    min_stock_threshold INT UNSIGNED NOT NULL DEFAULT 10,
    image_path VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_prod_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    address TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    address TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. SALES ORDER (SO) & GOODS ISSUE
CREATE TABLE IF NOT EXISTS sales_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    so_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    status ENUM('draft', 'pending_approval', 'approved', 'rejected', 'fulfilled', 'cancelled') NOT NULL DEFAULT 'draft',
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_so_cust FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_so_wh FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_so_creator FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_so_approver FOREIGN KEY (approved_by) REFERENCES users(id),
    CONSTRAINT chk_so_sod CHECK (approved_by IS NULL OR approved_by != created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sales_order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_soi_so FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_soi_prod FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS goods_issues (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gi_number VARCHAR(50) NOT NULL UNIQUE,
    sales_order_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    issued_by BIGINT UNSIGNED NOT NULL,
    issue_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gi_so FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id),
    CONSTRAINT fk_gi_wh FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_gi_issuer FOREIGN KEY (issued_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS goods_issue_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goods_issue_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity_issued INT UNSIGNED NOT NULL,
    CONSTRAINT fk_gii_gi FOREIGN KEY (goods_issue_id) REFERENCES goods_issues(id) ON DELETE CASCADE,
    CONSTRAINT fk_gii_prod FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. PURCHASE ORDER (PO) & GOODS RECEIPT
CREATE TABLE IF NOT EXISTS purchase_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) NOT NULL UNIQUE,
    supplier_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    status ENUM('draft', 'ordered', 'sent_to_supplier', 'partially_received', 'received', 'goods_received', 'cancelled') NOT NULL DEFAULT 'draft',
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_po_sup FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_po_wh FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_po_creator FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_po_approver FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    quantity_received INT UNSIGNED NOT NULL DEFAULT 0,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_poi_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_poi_prod FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS goods_receipts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gr_number VARCHAR(50) NOT NULL UNIQUE,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    received_by BIGINT UNSIGNED NOT NULL,
    receipt_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gr_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    CONSTRAINT fk_gr_wh FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_gr_receiver FOREIGN KEY (received_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS goods_receipt_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goods_receipt_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity_received INT UNSIGNED NOT NULL,
    CONSTRAINT fk_gri_gr FOREIGN KEY (goods_receipt_id) REFERENCES goods_receipts(id) ON DELETE CASCADE,
    CONSTRAINT fk_gri_prod FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. SALDO STOK MULTIGUDANG & AUDIT TRAIL IMMUTABLE STOCK LEDGER
CREATE TABLE IF NOT EXISTS inventory_stocks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity_on_hand INT NOT NULL DEFAULT 0,
    quantity_reserved INT NOT NULL DEFAULT 0,
    quantity_incoming INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_wh_prod (warehouse_id, product_id),
    CONSTRAINT fk_stk_wh FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_stk_prod FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT chk_stk_qty_positive CHECK (quantity_on_hand >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_ledger (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    movement_type ENUM('GOODS_RECEIPT', 'GOODS_ISSUE', 'TRANSFER_IN', 'TRANSFER_OUT', 'ADJUSTMENT') NOT NULL,
    quantity_delta INT NOT NULL,
    balance_after INT NOT NULL,
    reference_doc_type ENUM('PO', 'SO', 'TRANSFER', 'ADJUSTMENT') NOT NULL,
    reference_doc_number VARCHAR(50) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ledger_wh_prod (warehouse_id, product_id, created_at),
    INDEX idx_ledger_doc (reference_doc_type, reference_doc_number),
    CONSTRAINT fk_ledg_wh FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    CONSTRAINT fk_ledg_prod FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_ledg_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 6. DATA AWAL (SEED DATA DEFAULT)
-- Memenuhi Spesifikasi §7.1: Minimal 30 produk, 25 order, 3 role
-- ==============================================================================

-- A. USERS (1 Admin, 2 Sales, 2 Warehouse Staff)
-- Default Password: Admin12345678!
INSERT INTO users (id, name, email, password_hash, role, assigned_warehouse_id, status) VALUES
(1, 'Administrator Utama', 'admin@example.com', '$2y$10$AFSmpTOYUP8uhJjUj5anguvmkkr4UCe6s1CNM4YkSD/7Xv9Fldg02', 'admin', NULL, 'active'),
(2, 'Ahmad Sales Staff', 'sales@example.com', '$2y$10$I89Fy0JyYttLC6dN86h60uS31XopxJpeKW9nVQcpZBeWj4hOFOqMO', 'sales', 1, 'active'),
(3, 'Dewi Sales Senior', 'sales2@example.com', '$2y$10$I89Fy0JyYttLC6dN86h60uS31XopxJpeKW9nVQcpZBeWj4hOFOqMO', 'sales', 2, 'active'),
(4, 'Budi Gudang Surabaya', 'warehouse@example.com', '$2y$10$4CeZBDlMBCMcDaV1.FsL3OtWRMzFmWLfIkqHI0fmbcS4YhN/utqLS', 'warehouse', 2, 'active'),
(5, 'Joko Gudang Jakarta', 'warehouse2@example.com', '$2y$10$4CeZBDlMBCMcDaV1.FsL3OtWRMzFmWLfIkqHI0fmbcS4YhN/utqLS', 'warehouse', 1, 'active')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- B. GUDANG (3 Gudang Multi-Lokasi)
INSERT INTO warehouses (id, code, name, city, address, is_active) VALUES
(1, 'WH-JKT', 'Gudang Utama Jakarta', 'Jakarta Barat', 'Jl. Daan Mogot KM 12 No. 45', 1),
(2, 'WH-SBY', 'Gudang Cabang Surabaya', 'Surabaya Timur', 'Kawasan Industri Rungkut Blok B-8', 1),
(3, 'WH-BDG', 'Gudang Cabang Bandung', 'Bandung Selatan', 'Jl. Soekarno Hatta No. 520', 1)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- C. KATEGORI
INSERT INTO categories (id, code, name, description) VALUES
(1, 'CAT-ELK', 'Elektronik & Gadget', 'Perangkat komputer, laptop, dan peripheral premium'),
(2, 'CAT-ACS', 'Aksesoris & Peripheral', 'Keyboard, mouse, monitor, kabel, dan ergonomis'),
(3, 'CAT-NET', 'Networking & Server', 'Router, switch, dan perangkat infrastruktur jaringan'),
(4, 'CAT-OFF', 'Peralatan Kantor', 'Alat tulis, printer, proyektor, dan konsumabel kantor')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- D. SUPPLIER & CUSTOMER
INSERT INTO suppliers (id, code, name, contact_person, phone, email, address, is_active) VALUES
(1, 'SUP-001', 'PT Supplier Utama Indonesia', 'Agus Wijaya', '021-5550192', 'order@supplierutama.co.id', 'Kawasan Industri Pulogadung, Jakarta', 1),
(2, 'SUP-002', 'Global Tech Components Ltd', 'David Chen', '021-5550888', 'sales@globaltechcomp.com', 'Mega Kuningan, Jakarta Selatan', 1),
(3, 'SUP-003', 'PT Sentosa Distribusi Solusi', 'Bambang Kusuma', '022-7788990', 'kontak@sentosasolusi.co.id', 'Soekarno Hatta Technopark, Bandung', 1)
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO customers (id, code, name, contact_person, phone, email, address, is_active) VALUES
(1, 'CUST-001', 'PT Nusantara Jaya', 'Budi Santoso', '081234567890', 'budi@nusantarajaya.co.id', 'Sudirman Central Business District, Jakarta', 1),
(2, 'CUST-002', 'CV Mandiri Tech', 'Siti Rahma', '081987654321', 'procurement@mandiritech.com', 'Jl. Pemuda No. 88, Surabaya', 1),
(3, 'CUST-003', 'Toko Anugerah', 'Hendra Tan', '085678901234', 'hendra@anugerahstore.id', 'Jl. Asia Afrika No. 12, Bandung', 1),
(4, 'CUST-004', 'PT Sinar Abadi Kreasi', 'Rina Marlina', '082133445566', 'rina@sinarabadi.co.id', 'Kawasan Industri Cikarang, Bekasi', 1)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- E. 32 PRODUK DENGAN VARIASI REORDER POINT & HARGA
INSERT INTO products (id, sku, name, category_id, unit, purchase_price, selling_price, min_stock_threshold, is_active) VALUES
(1, 'PRD-001', 'Laptop Pro 15 Inch M2', 1, 'unit', 12000000.00, 15000000.00, 10, 1),
(2, 'PRD-002', 'Monitor 27 Inch 4K HDR', 2, 'unit', 3200000.00, 4500000.00, 15, 1),
(3, 'PRD-003', 'Keyboard Mekanikal RGB', 2, 'unit', 600000.00, 850000.00, 20, 1),
(4, 'PRD-004', 'Mouse Ergonomis Wireless', 2, 'unit', 250000.00, 375000.00, 15, 1),
(5, 'PRD-005', 'Router Gigabit WiFi 6', 3, 'unit', 850000.00, 1250000.00, 12, 1),
(6, 'PRD-006', 'Switch Managed 24 Port', 3, 'unit', 2100000.00, 2900000.00, 8, 1),
(7, 'PRD-007', 'Printer Laser Duplex', 4, 'unit', 1800000.00, 2400000.00, 5, 1),
(8, 'PRD-008', 'Kabel LAN Cat6 305m Roll', 3, 'roll', 750000.00, 1100000.00, 10, 1),
(9, 'PRD-009', 'SSD NVMe 1TB PCIe 4.0', 1, 'unit', 950000.00, 1350000.00, 25, 1),
(10, 'PRD-010', 'Webcam Full HD 1080p', 2, 'unit', 350000.00, 520000.00, 15, 1),
(11, 'PRD-011', 'Headset Noise Cancelling', 2, 'unit', 800000.00, 1150000.00, 10, 1),
(12, 'PRD-012', 'Docking Station USB-C', 2, 'unit', 650000.00, 950000.00, 12, 1),
(13, 'PRD-013', 'UPS 1200VA Backup', 3, 'unit', 1100000.00, 1600000.00, 6, 1),
(14, 'PRD-014', 'Server Rack 19 Inch 12U', 3, 'unit', 1750000.00, 2500000.00, 4, 1),
(15, 'PRD-015', 'Proyektor Mini Portable', 4, 'unit', 2200000.00, 3100000.00, 5, 1),
(16, 'PRD-016', 'Toner Printer Hitam High-Yield', 4, 'unit', 280000.00, 420000.00, 30, 1),
(17, 'PRD-017', 'Kertas A4 80gsm Box 5 Rim', 4, 'box', 220000.00, 290000.00, 40, 1),
(18, 'PRD-018', 'Monitor Arm Dual Gas Spring', 2, 'unit', 450000.00, 680000.00, 10, 1),
(19, 'PRD-019', 'Access Point Outdoor PoE', 3, 'unit', 1350000.00, 1950000.00, 8, 1),
(20, 'PRD-020', 'Kabel HDMI 2.1 4K 3m', 2, 'pcs', 75000.00, 125000.00, 50, 1),
(21, 'PRD-021', 'RAM DDR4 16GB 3200MHz', 1, 'unit', 420000.00, 620000.00, 20, 1),
(22, 'PRD-022', 'RAM DDR5 32GB 5600MHz', 1, 'unit', 1250000.00, 1750000.00, 15, 1),
(23, 'PRD-023', 'External Harddrive 2TB', 1, 'unit', 780000.00, 1100000.00, 12, 1),
(24, 'PRD-024', 'Flashdisk 128GB USB 3.2', 1, 'pcs', 95000.00, 150000.00, 30, 1),
(25, 'PRD-025', 'Barcode Scanner 2D QR', 4, 'unit', 450000.00, 690000.00, 8, 1),
(26, 'PRD-026', 'Thermal Receipt Printer', 4, 'unit', 580000.00, 890000.00, 10, 1),
(27, 'PRD-027', 'Cash Drawer RJ11 Metal', 4, 'unit', 350000.00, 520000.00, 6, 1),
(28, 'PRD-028', 'Patch Panel Cat6 24 Port', 3, 'unit', 380000.00, 550000.00, 10, 1),
(29, 'PRD-029', 'Fiber Optic Media Converter', 3, 'pair', 290000.00, 450000.00, 12, 1),
(30, 'PRD-030', 'Crimping Tool RJ45 Pro', 3, 'unit', 120000.00, 195000.00, 15, 1),
(31, 'PRD-031', 'Standing Desk Converter', 2, 'unit', 1400000.00, 2100000.00, 5, 1),
(32, 'PRD-032', 'Desk Mat Kulit PU XL', 2, 'pcs', 85000.00, 140000.00, 20, 1)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- F. STOK MULTIGUDANG (Termasuk produk di bawah reorder point / low stock untuk uji dashboard & script)
INSERT INTO inventory_stocks (warehouse_id, product_id, quantity_on_hand, quantity_reserved, quantity_incoming) VALUES
-- PRD-001 (Laptop): normal (total: 45, reorder: 10)
(1, 1, 25, 0, 0), (2, 1, 12, 0, 0), (3, 1, 8, 0, 0),
-- PRD-002 (Monitor): LOW STOCK (total: 7, reorder: 15) -> di bawah reorder point!
(1, 2, 5, 0, 15), (2, 2, 2, 0, 0), (3, 2, 0, 0, 0),
-- PRD-003 (Keyboard): normal (total: 73, reorder: 20)
(1, 3, 40, 0, 0), (2, 3, 18, 0, 0), (3, 3, 15, 0, 0),
-- PRD-004 (Mouse): LOW STOCK (total: 4, reorder: 15) -> di bawah reorder point!
(1, 4, 3, 0, 0), (2, 4, 1, 0, 0), (3, 4, 0, 0, 0),
-- PRD-005 (Router): normal (total: 28, reorder: 12)
(1, 5, 15, 0, 0), (2, 5, 8, 0, 0), (3, 5, 5, 0, 0),
-- PRD-006 (Switch): LOW STOCK (total: 5, reorder: 8) -> di bawah reorder point!
(1, 6, 2, 0, 0), (2, 6, 2, 0, 0), (3, 6, 1, 0, 0),
-- PRD-007 (Printer): normal (total: 10, reorder: 5)
(1, 7, 5, 0, 0), (2, 7, 3, 0, 0), (3, 7, 2, 0, 0),
-- PRD-008 (Kabel LAN): normal (total: 35, reorder: 10)
(1, 8, 20, 0, 0), (2, 8, 10, 0, 0), (3, 8, 5, 0, 0),
-- PRD-009 (SSD): normal (total: 60, reorder: 25)
(1, 9, 30, 0, 0), (2, 9, 20, 0, 0), (3, 9, 10, 0, 0),
-- PRD-010 (Webcam): LOW STOCK (total: 8, reorder: 15) -> di bawah reorder point!
(1, 10, 4, 0, 0), (2, 10, 4, 0, 0), (3, 10, 0, 0, 0),
-- PRD-011 s.d PRD-032
(1, 11, 15, 0, 0), (2, 11, 10, 0, 0), (3, 11, 5, 0, 0),
(1, 12, 18, 0, 0), (2, 12, 12, 0, 0), (3, 12, 8, 0, 0),
(1, 13, 8, 0, 0), (2, 13, 4, 0, 0), (3, 13, 3, 0, 0),
(1, 14, 5, 0, 0), (2, 14, 2, 0, 0), (3, 14, 2, 0, 0),
(1, 15, 7, 0, 0), (2, 15, 4, 0, 0), (3, 15, 2, 0, 0),
(1, 16, 45, 0, 0), (2, 16, 25, 0, 0), (3, 16, 20, 0, 0),
(1, 17, 80, 0, 0), (2, 17, 50, 0, 0), (3, 17, 30, 0, 0),
(1, 18, 15, 0, 0), (2, 18, 10, 0, 0), (3, 18, 5, 0, 0),
(1, 19, 12, 0, 0), (2, 19, 6, 0, 0), (3, 19, 4, 0, 0),
(1, 20, 70, 0, 0), (2, 20, 40, 0, 0), (3, 20, 30, 0, 0),
(1, 21, 35, 0, 0), (2, 21, 20, 0, 0), (3, 21, 15, 0, 0),
(1, 22, 25, 0, 0), (2, 22, 15, 0, 0), (3, 22, 10, 0, 0),
(1, 23, 20, 0, 0), (2, 23, 15, 0, 0), (3, 23, 10, 0, 0),
(1, 24, 50, 0, 0), (2, 24, 30, 0, 0), (3, 24, 20, 0, 0),
(1, 25, 12, 0, 0), (2, 25, 8, 0, 0), (3, 25, 5, 0, 0),
(1, 26, 15, 0, 0), (2, 26, 10, 0, 0), (3, 26, 6, 0, 0),
(1, 27, 10, 0, 0), (2, 27, 6, 0, 0), (3, 27, 4, 0, 0),
(1, 28, 14, 0, 0), (2, 28, 8, 0, 0), (3, 28, 6, 0, 0),
(1, 29, 18, 0, 0), (2, 29, 10, 0, 0), (3, 29, 8, 0, 0),
(1, 30, 22, 0, 0), (2, 30, 15, 0, 0), (3, 30, 10, 0, 0),
(1, 31, 8, 0, 0), (2, 31, 4, 0, 0), (3, 31, 3, 0, 0),
(1, 32, 30, 0, 0), (2, 32, 20, 0, 0), (3, 32, 15, 0, 0)
ON DUPLICATE KEY UPDATE quantity_on_hand=VALUES(quantity_on_hand);

-- G. 15 SALES ORDERS (Variasi Status: draft, pending_approval, approved, fulfilled, cancelled)
INSERT INTO sales_orders (id, so_number, customer_id, warehouse_id, status, total_amount, created_by, approved_by, notes, created_at) VALUES
(101, 'SO-2026-001', 1, 1, 'pending_approval', 75000000.00, 2, NULL, 'Pengadaan batch kantor cabang Jakarta', '2026-09-20 08:30:00'),
(102, 'SO-2026-002', 2, 2, 'approved', 8500000.00, 2, 1, 'Disetujui Admin, siap proses Goods Issue Gudang Surabaya', '2026-09-21 09:15:00'),
(103, 'SO-2026-003', 3, 3, 'fulfilled', 9000000.00, 2, 1, 'Pesanan selesai dan barang diterima pelanggan', '2026-09-22 13:00:00'),
(104, 'SO-2026-004', 4, 1, 'draft', 18500000.00, 3, NULL, 'Draft pesanan baru dari PT Sinar Abadi', '2026-09-23 10:00:00'),
(105, 'SO-2026-005', 1, 2, 'pending_approval', 14500000.00, 3, NULL, 'Menunggu persetujuan manajemen', '2026-09-23 11:30:00'),
(106, 'SO-2026-006', 2, 3, 'cancelled', 4500000.00, 2, NULL, 'Dibatalkan customer karena perubahan spek', '2026-09-23 14:00:00'),
(107, 'SO-2026-007', 3, 1, 'approved', 22000000.00, 2, 1, 'Approved, siap pick and pack', '2026-09-24 09:00:00'),
(108, 'SO-2026-008', 4, 2, 'fulfilled', 6200000.00, 3, 1, 'Terkirim via kurir internal Surabaya', '2026-09-24 10:30:00'),
(109, 'SO-2026-009', 1, 3, 'pending_approval', 35000000.00, 2, NULL, 'PO besar dari klien korporat', '2026-09-24 13:00:00'),
(110, 'SO-2026-010', 2, 1, 'draft', 3200000.00, 2, NULL, 'Draft penawaran harga', '2026-09-25 08:45:00'),
(111, 'SO-2026-011', 3, 2, 'approved', 11500000.00, 3, 1, 'Approved Admin', '2026-09-25 09:30:00'),
(112, 'SO-2026-012', 4, 3, 'fulfilled', 8900000.00, 2, 1, 'Selesai fulfillment Bandung', '2026-09-25 11:15:00'),
(113, 'SO-2026-013', 1, 1, 'cancelled', 15000000.00, 3, NULL, 'Order dibatalkan oleh sales', '2026-09-25 14:20:00'),
(114, 'SO-2026-014', 2, 2, 'pending_approval', 9800000.00, 2, NULL, 'Menunggu approval admin', '2026-09-26 09:10:00'),
(115, 'SO-2026-015', 3, 1, 'fulfilled', 16500000.00, 3, 1, 'Fulfillment selesai di Jakarta', '2026-09-26 14:00:00')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- Sales Order Items
INSERT INTO sales_order_items (id, sales_order_id, product_id, quantity, unit_price, subtotal) VALUES
(1, 101, 1, 5, 15000000.00, 75000000.00),
(2, 102, 3, 10, 850000.00, 8500000.00),
(3, 103, 2, 2, 4500000.00, 9000000.00),
(4, 104, 5, 10, 1250000.00, 12500000.00),
(5, 104, 8, 5, 1100000.00, 5500000.00),
(6, 105, 9, 10, 1350000.00, 13500000.00),
(7, 106, 2, 1, 4500000.00, 4500000.00),
(8, 107, 1, 1, 15000000.00, 15000000.00),
(9, 107, 7, 2, 2400000.00, 4800000.00),
(10, 108, 21, 10, 620000.00, 6200000.00),
(11, 109, 22, 20, 1750000.00, 35000000.00),
(12, 110, 10, 5, 520000.00, 2600000.00),
(13, 111, 11, 10, 1150000.00, 11500000.00),
(14, 112, 26, 10, 890000.00, 8900000.00),
(15, 113, 1, 1, 15000000.00, 15000000.00),
(16, 114, 12, 10, 950000.00, 9500000.00),
(17, 115, 15, 5, 3100000.00, 15500000.00)
ON DUPLICATE KEY UPDATE quantity=VALUES(quantity);

-- H. 12 PURCHASE ORDERS (Variasi Status: draft, ordered, goods_received, cancelled)
INSERT INTO purchase_orders (id, po_number, supplier_id, warehouse_id, status, total_amount, created_by, approved_by, notes, created_at) VALUES
(501, 'PO-2026-081', 1, 1, 'sent_to_supplier', 48000000.00, 1, 1, 'Restock Monitor 4K Jakarta', '2026-09-18 07:45:00'),
(502, 'PO-2026-082', 2, 2, 'goods_received', 30000000.00, 4, 1, 'Penerimaan batch Keyboard Surabaya', '2026-09-19 08:00:00'),
(503, 'PO-2026-083', 3, 3, 'goods_received', 15000000.00, 5, 1, 'Pengadaan kabel & aksesoris Bandung', '2026-09-20 09:30:00'),
(504, 'PO-2026-084', 1, 1, 'draft', 24000000.00, 5, NULL, 'Usulan draft pengadaan laptop baru', '2026-09-21 11:00:00'),
(505, 'PO-2026-085', 2, 2, 'sent_to_supplier', 18500000.00, 1, 1, 'Order resmi terkirim ke Global Tech', '2026-09-22 10:15:00'),
(506, 'PO-2026-086', 3, 3, 'cancelled', 7500000.00, 4, NULL, 'Dibatalkan karena harga supplier naik', '2026-09-22 14:00:00'),
(507, 'PO-2026-087', 1, 1, 'goods_received', 50000000.00, 1, 1, 'Penerimaan batch server & switch', '2026-09-23 09:00:00'),
(508, 'PO-2026-088', 2, 2, 'sent_to_supplier', 12000000.00, 4, 1, 'Restock RAM dan SSD Surabaya', '2026-09-23 15:30:00'),
(509, 'PO-2026-089', 3, 1, 'draft', 9500000.00, 5, NULL, 'Draft usulan pengadaan printer toner', '2026-09-24 08:30:00'),
(510, 'PO-2026-090', 1, 2, 'goods_received', 22500000.00, 1, 1, 'Diterima lengkap gudang Surabaya', '2026-09-24 13:45:00'),
(511, 'PO-2026-091', 2, 3, 'sent_to_supplier', 14000000.00, 1, 1, 'Pengiriman batch kedua', '2026-09-25 10:00:00'),
(512, 'PO-2026-092', 3, 1, 'cancelled', 5000000.00, 5, NULL, 'Dibatalkan oleh warehouse staff', '2026-09-25 16:00:00')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- Purchase Order Items
INSERT INTO purchase_order_items (id, purchase_order_id, product_id, quantity, quantity_received, unit_price, subtotal) VALUES
(1, 501, 2, 15, 0, 3200000.00, 48000000.00),
(2, 502, 3, 50, 50, 600000.00, 30000000.00),
(3, 503, 8, 20, 20, 750000.00, 15000000.00),
(4, 504, 1, 2, 0, 12000000.00, 24000000.00),
(5, 505, 9, 20, 0, 925000.00, 18500000.00),
(6, 506, 4, 30, 0, 250000.00, 7500000.00),
(7, 507, 6, 20, 20, 2100000.00, 42000000.00),
(8, 508, 21, 30, 0, 400000.00, 12000000.00),
(9, 509, 16, 30, 0, 280000.00, 8400000.00),
(10, 510, 11, 25, 25, 800000.00, 20000000.00),
(11, 511, 23, 20, 0, 700000.00, 14000000.00),
(12, 512, 20, 50, 0, 75000.00, 3750000.00)
ON DUPLICATE KEY UPDATE quantity=VALUES(quantity);

-- I. STOCK LEDGER (Audit Trail Mutasi Stok Terpilih)
INSERT INTO stock_ledger (id, warehouse_id, product_id, movement_type, quantity_delta, balance_after, reference_doc_type, reference_doc_number, user_id, notes, created_at) VALUES
(1, 2, 3, 'GOODS_RECEIPT', 50, 18, 'PO', 'PO-2026-082', 4, 'Penerimaan barang dari supplier PO-2026-082', '2026-09-19 09:15:00'),
(2, 3, 2, 'GOODS_ISSUE', -2, 0, 'SO', 'SO-2026-003', 1, 'Pengeluaran barang SO Toko Anugerah', '2026-09-22 13:30:00'),
(3, 1, 1, 'GOODS_RECEIPT', 10, 25, 'PO', 'PO-2026-080', 1, 'Restock batch Laptop Pro 15 Inch', '2026-09-22 16:40:00'),
(4, 2, 21, 'GOODS_ISSUE', -10, 20, 'SO', 'SO-2026-008', 4, 'Goods issue pesanan CV Mandiri Tech', '2026-09-24 11:00:00'),
(5, 3, 26, 'GOODS_ISSUE', -10, 6, 'SO', 'SO-2026-012', 1, 'Goods issue pesanan Toko Anugerah Bandung', '2026-09-25 11:30:00')
ON DUPLICATE KEY UPDATE balance_after=VALUES(balance_after);

SET FOREIGN_KEY_CHECKS = 1;
