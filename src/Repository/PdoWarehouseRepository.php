<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Warehouse;
use PDO;

final class PdoWarehouseRepository implements WarehouseRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    /** @return list<Warehouse> */
    public function findAll(): array
    {
        $stmt = $this->pdo->prepare('SELECT id, code, name, city, address, is_active FROM warehouses ORDER BY id ASC');
        $stmt->execute();
        /** @var list<array{id: int|string, code: string, name: string, city: string, address: string|null, is_active: int|string}> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $warehouses = [];
        foreach ($rows as $row) {
            $warehouses[] = new Warehouse(
                (int) $row['id'],
                $row['code'],
                $row['name'],
                $row['city'],
                $row['address'],
                (int) $row['is_active']
            );
        }

        return $warehouses;
    }

    public function findById(int $id): ?Warehouse
    {
        $stmt = $this->pdo->prepare('SELECT id, code, name, city, address, is_active FROM warehouses WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        /** @var array{id: int|string, code: string, name: string, city: string, address: string|null, is_active: int|string}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new Warehouse(
            (int) $row['id'],
            $row['code'],
            $row['name'],
            $row['city'],
            $row['address'],
            (int) $row['is_active']
        );
    }

    public function findByCode(string $code, ?int $excludeId = null): ?Warehouse
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare('SELECT id, code, name, city, address, is_active FROM warehouses WHERE code = ? AND id != ? LIMIT 1');
            $stmt->execute([$code, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT id, code, name, city, address, is_active FROM warehouses WHERE code = ? LIMIT 1');
            $stmt->execute([$code]);
        }

        /** @var array{id: int|string, code: string, name: string, city: string, address: string|null, is_active: int|string}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new Warehouse(
            (int) $row['id'],
            $row['code'],
            $row['name'],
            $row['city'],
            $row['address'],
            (int) $row['is_active']
        );
    }

    public function create(Warehouse $warehouse): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO warehouses (code, name, city, address, is_active) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$warehouse->code, $warehouse->name, $warehouse->city, $warehouse->address ?: null, $warehouse->isActive]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(Warehouse $warehouse): void
    {
        $stmt = $this->pdo->prepare('UPDATE warehouses SET code = ?, name = ?, city = ?, address = ?, is_active = ? WHERE id = ?');
        $stmt->execute([$warehouse->code, $warehouse->name, $warehouse->city, $warehouse->address ?: null, $warehouse->isActive, $warehouse->id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM warehouses WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function getPhysicalStock(int $warehouseId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(quantity_on_hand), 0) FROM inventory_stocks WHERE warehouse_id = ?');
        $stmt->execute([$warehouseId]);
        return (int) $stmt->fetchColumn();
    }

    public function getOrderCount(int $warehouseId): int
    {
        $stmt = $this->pdo->prepare('SELECT (SELECT COUNT(*) FROM sales_orders WHERE warehouse_id = ?) + (SELECT COUNT(*) FROM purchase_orders WHERE warehouse_id = ?)');
        $stmt->execute([$warehouseId, $warehouseId]);
        return (int) $stmt->fetchColumn();
    }

    /** @return list<int> */
    public function getAllWarehouseIds(): array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM warehouses ORDER BY id ASC');
        $stmt->execute();
        /** @var list<int|string> $ids */
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return array_map(static fn($id) => (int) $id, $ids);
    }

    public function initWarehouseStockForProducts(int $warehouseId): void
    {
        $stmtProducts = $this->pdo->prepare('SELECT id FROM products');
        $stmtProducts->execute();
        /** @var list<int|string> $prodIds */
        $prodIds = $stmtProducts->fetchAll(PDO::FETCH_COLUMN);

        $insStk = $this->pdo->prepare('INSERT IGNORE INTO inventory_stocks (warehouse_id, product_id, quantity_on_hand, quantity_reserved, quantity_incoming) VALUES (?, ?, 0, 0, 0)');
        foreach ($prodIds as $pId) {
            $insStk->execute([$warehouseId, (int) $pId]);
        }
    }

    public function deleteStocksAndLedger(int $warehouseId): void
    {
        $delLedger = $this->pdo->prepare('DELETE FROM stock_ledger WHERE warehouse_id = ?');
        $delLedger->execute([$warehouseId]);

        $delStocks = $this->pdo->prepare('DELETE FROM inventory_stocks WHERE warehouse_id = ?');
        $delStocks->execute([$warehouseId]);
    }
}
