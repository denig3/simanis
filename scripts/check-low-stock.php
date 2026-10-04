<?php
/**
 * JOB-01: SCRIPT TERJADWAL PENGECEKAN PRODUK LOW STOCK (DI BAWAH REORDER POINT)
 * 
 * Script mandiri ini dijalankan di luar siklus request web (misal via Cron atau CLI / Docker exec).
 * Mengidentifikasi produk-produk yang stok totalnya telah mencapai atau di bawah batas minimum (reorder point),
 * untuk memberikan peringatan dini kepada Admin dan Tim Pengadaan (Warehouse Staff).
 *
 * Penggunaan:
 *   php scripts/check-low-stock.php
 *   docker compose exec app php scripts/check-low-stock.php
 */

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Infrastructure\Database;

if (PHP_SAPI !== 'cli') {
    echo "Script ini hanya dapat dijalankan melalui antarmuka Command Line (CLI).\n";
    exit(1);
}

echo "========================================================================================\n";
echo "       STOKORA INVENTORY SYSTEM - MONITORING STOK KRITIS & REORDER POINT (JOB-01)       \n";
echo "========================================================================================\n";
echo "Waktu Eksekusi: " . date('Y-m-d H:i:s') . "\n";
echo "Lingkungan: " . (getenv('APP_ENV') ?: 'production') . "\n\n";

try {
    $pdo = Database::connect();

    $stmt = $pdo->query("
        SELECT 
            p.id, 
            p.sku, 
            p.name, 
            c.name AS category_name, 
            p.unit, 
            p.min_stock_threshold AS reorder_point,
            COALESCE(SUM(s.quantity_on_hand), 0) AS total_stock
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN inventory_stocks s ON p.id = s.product_id
        WHERE p.is_active = 1
        GROUP BY p.id, p.sku, p.name, c.name, p.unit, p.min_stock_threshold
        HAVING total_stock <= reorder_point
        ORDER BY (total_stock - reorder_point) ASC, p.id ASC
    ");

    /** @var list<array{id: int|string, sku: string, name: string, category_name: string|null, unit: string, reorder_point: int|string, total_stock: int|string}> $lowStockItems */
    $lowStockItems = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    if (empty($lowStockItems)) {
        echo "[INFO] Status Aman: Seluruh produk berada di atas batas reorder point.\n";
        echo "Tidak ada tindakan pengadaan (Purchase Order) yang mendesak saat ini.\n";
        exit(0);
    }

    echo sprintf("[PERINGATAN] Ditemukan %d produk yang berada pada atau di bawah batas reorder point:\n\n", count($lowStockItems));

    printf("%-10s | %-30s | %-20s | %-8s | %-8s | %-8s | %-10s\n", 'SKU', 'Nama Produk', 'Kategori', 'Reorder', 'Stok', 'Defisit', 'Status');
    echo str_repeat('-', 106) . "\n";

    foreach ($lowStockItems as $item) {
        $reorder = (int) $item['reorder_point'];
        $stock = (int) $item['total_stock'];
        $deficit = $reorder - $stock;
        $status = ($stock === 0) ? 'HABIS' : 'KRITIS';

        printf(
            "%-10s | %-30s | %-20s | %-8d | %-8d | %-8d | %-10s\n",
            $item['sku'],
            mb_strimwidth($item['name'], 0, 30, '...'),
            mb_strimwidth($item['category_name'] ?? '-', 0, 20, '...'),
            $reorder,
            $stock,
            $deficit,
            $status
        );
    }

    echo str_repeat('-', 106) . "\n";
    echo "\nRekomendasi Tindakan:\n";
    echo "1. Warehouse Staff atau Admin disarankan segera menerbitkan Purchase Order (PO) ke Supplier terkait.\n";
    echo "2. Periksa antrean Goods Receipt untuk memastikan tidak ada pengiriman yang tertahan.\n";
    echo "========================================================================================\n";

    exit(0);
} catch (Throwable $e) {
    echo "[ERROR] Gagal menjalankan pengecekan stok: " . $e->getMessage() . "\n";
    exit(1);
}
