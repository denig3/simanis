<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Product;
use PDO;

final class PdoProductRepository implements ProductRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    /** @return list<Product> */
    public function findAllWithStock(): array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                p.id, p.sku, p.name, p.category_id, p.min_stock_threshold, p.selling_price, p.purchase_price, p.unit, p.is_active,
                c.name AS category_name,
                COALESCE(SUM(CASE WHEN s.warehouse_id = 1 THEN s.quantity_on_hand ELSE 0 END), 0) AS stock_jkt,
                COALESCE(SUM(CASE WHEN s.warehouse_id = 2 THEN s.quantity_on_hand ELSE 0 END), 0) AS stock_sby,
                COALESCE(SUM(CASE WHEN s.warehouse_id = 3 THEN s.quantity_on_hand ELSE 0 END), 0) AS stock_bdg,
                COALESCE(SUM(s.quantity_on_hand), 0) AS total_stock
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN inventory_stocks s ON p.id = s.product_id
            GROUP BY p.id, p.sku, p.name, p.category_id, p.min_stock_threshold, p.selling_price, p.purchase_price, p.unit, p.is_active, c.name
            ORDER BY p.id ASC
        ');
        $stmt->execute();
        /** @var list<array{id: int|string, sku: string, name: string, category_id: int|string, min_stock_threshold: int|string, selling_price: float|string, purchase_price: float|string, unit: string, is_active: int|string, category_name: string|null, stock_jkt: int|string, stock_sby: int|string, stock_bdg: int|string, total_stock: int|string}> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $products = [];
        foreach ($rows as $row) {
            $products[] = new Product(
                (int) $row['id'],
                $row['sku'],
                $row['name'],
                (int) $row['category_id'],
                $row['unit'],
                (float) $row['purchase_price'],
                (float) $row['selling_price'],
                (int) $row['min_stock_threshold'],
                (int) $row['is_active'],
                $row['category_name'],
                (int) $row['stock_jkt'],
                (int) $row['stock_sby'],
                (int) $row['stock_bdg'],
                (int) $row['total_stock']
            );
        }

        return $products;
    }

    public function findById(int $id): ?Product
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                p.id, p.sku, p.name, p.category_id, p.min_stock_threshold, p.selling_price, p.purchase_price, p.unit, p.is_active,
                c.name AS category_name,
                COALESCE(SUM(CASE WHEN s.warehouse_id = 1 THEN s.quantity_on_hand ELSE 0 END), 0) AS stock_jkt,
                COALESCE(SUM(CASE WHEN s.warehouse_id = 2 THEN s.quantity_on_hand ELSE 0 END), 0) AS stock_sby,
                COALESCE(SUM(CASE WHEN s.warehouse_id = 3 THEN s.quantity_on_hand ELSE 0 END), 0) AS stock_bdg,
                COALESCE(SUM(s.quantity_on_hand), 0) AS total_stock
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN inventory_stocks s ON p.id = s.product_id
            WHERE p.id = ?
            GROUP BY p.id, p.sku, p.name, p.category_id, p.min_stock_threshold, p.selling_price, p.purchase_price, p.unit, p.is_active, c.name
            LIMIT 1
        ');
        $stmt->execute([$id]);
        /** @var array{id: int|string, sku: string, name: string, category_id: int|string, min_stock_threshold: int|string, selling_price: float|string, purchase_price: float|string, unit: string, is_active: int|string, category_name: string|null, stock_jkt: int|string, stock_sby: int|string, stock_bdg: int|string, total_stock: int|string}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new Product(
            (int) $row['id'],
            $row['sku'],
            $row['name'],
            (int) $row['category_id'],
            $row['unit'],
            (float) $row['purchase_price'],
            (float) $row['selling_price'],
            (int) $row['min_stock_threshold'],
            (int) $row['is_active'],
            $row['category_name'],
            (int) $row['stock_jkt'],
            (int) $row['stock_sby'],
            (int) $row['stock_bdg'],
            (int) $row['total_stock']
        );
    }

    public function findBySku(string $sku, ?int $excludeId = null): ?Product
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare('SELECT id, sku, name FROM products WHERE sku = ? AND id != ? LIMIT 1');
            $stmt->execute([$sku, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT id, sku, name FROM products WHERE sku = ? LIMIT 1');
            $stmt->execute([$sku]);
        }

        /** @var array{id: int|string, sku: string, name: string}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new Product(
            (int) $row['id'],
            $row['sku'],
            $row['name'],
            0
        );
    }

    public function create(Product $product): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO products (sku, name, category_id, unit, purchase_price, selling_price, min_stock_threshold, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ');
        $stmt->execute([
            $product->sku,
            $product->name,
            $product->categoryId,
            $product->unit,
            $product->purchasePrice,
            $product->sellingPrice,
            $product->minStockThreshold
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(Product $product): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE products
            SET sku = ?, name = ?, category_id = ?, unit = ?, purchase_price = ?, selling_price = ?, min_stock_threshold = ?, is_active = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $product->sku,
            $product->name,
            $product->categoryId,
            $product->unit,
            $product->purchasePrice,
            $product->sellingPrice,
            $product->minStockThreshold,
            $product->isActive,
            $product->id
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** @return array{so: int, po: int} */
    public function getTransactionCount(int $productId): array
    {
        $chkSO = $this->pdo->prepare('SELECT COUNT(*) FROM sales_order_items WHERE product_id = ?');
        $chkSO->execute([$productId]);
        $soCount = (int) $chkSO->fetchColumn();

        $chkPO = $this->pdo->prepare('SELECT COUNT(*) FROM purchase_order_items WHERE product_id = ?');
        $chkPO->execute([$productId]);
        $poCount = (int) $chkPO->fetchColumn();

        return ['so' => $soCount, 'po' => $poCount];
    }

    public function getTotalStock(int $productId): int
    {
        $stkStmt = $this->pdo->prepare('SELECT COALESCE(SUM(quantity_on_hand), 0) FROM inventory_stocks WHERE product_id = ?');
        $stkStmt->execute([$productId]);
        return (int) $stkStmt->fetchColumn();
    }

    public function deleteStocksAndLedger(int $productId): void
    {
        $delLedger = $this->pdo->prepare('DELETE FROM stock_ledger WHERE product_id = ?');
        $delLedger->execute([$productId]);

        $delStocks = $this->pdo->prepare('DELETE FROM inventory_stocks WHERE product_id = ?');
        $delStocks->execute([$productId]);
    }

    /** @param array<int, int> $warehouseStocks */
    public function initProductStocks(int $productId, array $warehouseStocks, int $userId, string $sku): void
    {
        $whStmt = $this->pdo->prepare('SELECT id FROM warehouses');
        $whStmt->execute();
        /** @var list<int|string> $whList */
        $whList = $whStmt->fetchAll(PDO::FETCH_COLUMN);

        $insStock = $this->pdo->prepare('
            INSERT INTO inventory_stocks (warehouse_id, product_id, quantity_on_hand, quantity_reserved, quantity_incoming)
            VALUES (?, ?, ?, 0, 0)
        ');
        $insLedger = $this->pdo->prepare("
            INSERT INTO stock_ledger (warehouse_id, product_id, movement_type, quantity_delta, balance_after, reference_doc_type, reference_doc_number, user_id, notes)
            VALUES (?, ?, 'ADJUSTMENT', ?, ?, 'ADJUSTMENT', ?, ?, 'Saldo awal registrasi produk')
        ");

        foreach ($whList as $whIdRaw) {
            $whId = (int) $whIdRaw;
            $qty = $warehouseStocks[$whId] ?? 0;
            $insStock->execute([$whId, $productId, $qty]);

            if ($qty > 0) {
                $insLedger->execute([$whId, $productId, $qty, $qty, 'INIT-' . $sku, $userId]);
            }
        }
    }
}
