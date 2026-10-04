<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\ConflictException;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Model\PurchaseOrder;
use App\Model\SalesOrder;
use App\Repository\OrderRepositoryInterface;

final class OrderService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {}

    /** @return list<SalesOrder> */
    public function getAllSalesOrders(): array
    {
        return $this->orderRepository->findAllSalesOrders();
    }

    public function getSalesOrder(int $id): SalesOrder
    {
        $so = $this->orderRepository->findSalesOrderById($id);
        if ($so === null) {
            throw new NotFoundException("Sales Order #{$id} tidak ditemukan.");
        }
        return $so;
    }

    /**
     * @param array{id: int|string, role?: string, email?: string} $user
     * @param array<string, mixed> $input
     */
    public function createSalesOrder(array $user, array $input): int
    {
        $role = $user['role'] ?? 'sales';
        if ($role !== 'admin' && $role !== 'sales') {
            throw new ForbiddenException('Hanya Admin dan Sales yang dapat membuat Sales Order.');
        }

        $customerId = (int) ($input['customer_id'] ?? 0);
        $warehouseId = (int) ($input['warehouse_id'] ?? 0);
        $notes = isset($input['notes']) && is_string($input['notes']) ? trim($input['notes']) : null;
        $rawItems = $input['items'] ?? [];

        if ($customerId <= 0) {
            throw new ValidationException('Customer wajib dipilih.');
        }

        if ($warehouseId <= 0) {
            throw new ValidationException('Gudang tujuan wajib dipilih.');
        }

        if (!is_array($rawItems) || empty($rawItems)) {
            throw new ValidationException('Pesanan wajib memiliki minimal 1 item produk.');
        }

        /** @var list<array{product_id: int, quantity: int, unit_price: float}> $validatedItems */
        $validatedItems = [];
        foreach ($rawItems as $item) {
            if (!is_array($item)) {
                throw new ValidationException('Format item produk tidak valid.');
            }
            $pId = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0.0);

            if ($pId <= 0) {
                throw new ValidationException('Produk tidak valid.');
            }
            if ($qty <= 0) {
                throw new ValidationException('Kuantitas produk harus lebih dari 0.');
            }
            if ($price < 0) {
                throw new ValidationException('Harga satuan tidak boleh negatif.');
            }

            $validatedItems[] = [
                'product_id' => $pId,
                'quantity' => $qty,
                'unit_price' => $price,
            ];
        }

        return $this->orderRepository->createSalesOrder(
            $customerId,
            $warehouseId,
            (int) $user['id'],
            $notes,
            $validatedItems
        );
    }

    /**
     * @param array{id: int|string, role?: string} $user
     */
    public function submitSalesOrder(array $user, int $orderId): void
    {
        $so = $this->getSalesOrder($orderId);

        if ($so->status !== 'draft') {
            throw new ConflictException("Hanya Sales Order berstatus 'draft' yang dapat diajukan. Status saat ini: {$so->status}.");
        }

        $role = $user['role'] ?? 'sales';
        if ($role === 'sales' && $so->createdBy !== (int) $user['id']) {
            throw new ForbiddenException('Sales hanya dapat mengajukan Sales Order buatannya sendiri.');
        }

        $this->orderRepository->updateSalesOrderStatus($orderId, 'pending_approval');
    }

    /**
     * Menyetujui Sales Order dengan penegakan mutlak Segregation of Duties (SOD).
     *
     * @param array{id: int|string, role?: string} $user
     */
    public function approveSalesOrder(array $user, int $orderId): void
    {
        $role = $user['role'] ?? '';
        if ($role !== 'admin') {
            throw new ForbiddenException('Hanya Admin yang berwenang menyetujui Sales Order.');
        }

        $so = $this->getSalesOrder($orderId);

        // ATURAN SEGREGATION OF DUTIES: Sales yang membuat order tidak boleh menyetujui order yang sama
        if ($so->createdBy === (int) $user['id']) {
            throw new ForbiddenException('Pelanggaran Segregation of Duties: Pembuat order dilarang menyetujui order miliknya sendiri.');
        }

        if ($so->status !== 'pending_approval') {
            throw new ConflictException("Hanya Sales Order berstatus 'pending_approval' yang dapat disetujui. Status saat ini: {$so->status}.");
        }

        $this->orderRepository->updateSalesOrderStatus($orderId, 'approved', (int) $user['id']);
    }

    /**
     * @param array{id: int|string, role?: string} $user
     */
    public function rejectSalesOrder(array $user, int $orderId, ?string $reason = null): void
    {
        $role = $user['role'] ?? '';
        if ($role !== 'admin') {
            throw new ForbiddenException('Hanya Admin yang berwenang menolak Sales Order.');
        }

        $so = $this->getSalesOrder($orderId);

        if ($so->status !== 'pending_approval') {
            throw new ConflictException("Hanya order 'pending_approval' yang dapat ditolak.");
        }

        $this->orderRepository->updateSalesOrderStatus($orderId, 'cancelled', (int) $user['id']);
    }

    /**
     * Memproses Goods Issue untuk Sales Order yang disetujui.
     * Mengurangi stok dengan pessimistic lock (ARCH-02).
     *
     * @param array{id: int|string, role?: string} $user
     */
    public function fulfillSalesOrder(array $user, int $orderId): void
    {
        $role = $user['role'] ?? '';
        if ($role !== 'warehouse' && $role !== 'admin') {
            throw new ForbiddenException('Hanya Staff Gudang dan Admin yang dapat memproses pengeluaran barang (Goods Issue).');
        }

        $this->orderRepository->processGoodsIssue($orderId, (int) $user['id']);
    }

    /**
     * @param array{id: int|string, role?: string} $user
     */
    public function cancelSalesOrder(array $user, int $orderId): void
    {
        $so = $this->getSalesOrder($orderId);

        if ($so->status === 'fulfilled') {
            throw new ConflictException('Pesanan yang sudah fulfilled tidak dapat dibatalkan.');
        }

        if ($so->status === 'cancelled') {
            throw new ConflictException('Pesanan sudah dibatalkan sebelumnya.');
        }

        $role = $user['role'] ?? '';
        if ($role === 'sales' && $so->createdBy !== (int) $user['id']) {
            throw new ForbiddenException('Sales hanya dapat membatalkan order miliknya sendiri.');
        }

        $this->orderRepository->updateSalesOrderStatus($orderId, 'cancelled');
    }

    /** @return list<PurchaseOrder> */
    public function getAllPurchaseOrders(): array
    {
        return $this->orderRepository->findAllPurchaseOrders();
    }

    public function getPurchaseOrder(int $id): PurchaseOrder
    {
        $po = $this->orderRepository->findPurchaseOrderById($id);
        if ($po === null) {
            throw new NotFoundException("Purchase Order #{$id} tidak ditemukan.");
        }
        return $po;
    }

    /**
     * @param array{id: int|string, role?: string} $user
     * @param array<string, mixed> $input
     */
    public function createPurchaseOrder(array $user, array $input): int
    {
        $role = $user['role'] ?? '';
        if ($role !== 'admin' && $role !== 'warehouse') {
            throw new ForbiddenException('Hanya Admin dan Staff Gudang yang dapat membuat/mengusulkan Purchase Order.');
        }

        $supplierId = (int) ($input['supplier_id'] ?? 0);
        $warehouseId = (int) ($input['warehouse_id'] ?? 0);
        $notes = isset($input['notes']) && is_string($input['notes']) ? trim($input['notes']) : null;
        $rawItems = $input['items'] ?? [];

        if ($supplierId <= 0) {
            throw new ValidationException('Supplier wajib dipilih.');
        }

        if ($warehouseId <= 0) {
            throw new ValidationException('Gudang penerima wajib dipilih.');
        }

        if (!is_array($rawItems) || empty($rawItems)) {
            throw new ValidationException('Purchase Order wajib memiliki minimal 1 item produk.');
        }

        /** @var list<array{product_id: int, quantity: int, unit_price: float}> $validatedItems */
        $validatedItems = [];
        foreach ($rawItems as $item) {
            if (!is_array($item)) {
                throw new ValidationException('Format item produk tidak valid.');
            }
            $pId = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0.0);

            if ($pId <= 0) {
                throw new ValidationException('Produk tidak valid.');
            }
            if ($qty <= 0) {
                throw new ValidationException('Kuantitas pengadaan harus lebih dari 0.');
            }
            if ($price < 0) {
                throw new ValidationException('Harga beli satuan tidak boleh negatif.');
            }

            $validatedItems[] = [
                'product_id' => $pId,
                'quantity' => $qty,
                'unit_price' => $price,
            ];
        }

        return $this->orderRepository->createPurchaseOrder(
            $supplierId,
            $warehouseId,
            (int) $user['id'],
            $notes,
            $validatedItems
        );
    }

    /**
     * @param array{id: int|string, role?: string} $user
     */
    public function orderPurchaseOrder(array $user, int $orderId): void
    {
        $po = $this->getPurchaseOrder($orderId);

        if ($po->status !== 'draft') {
            throw new ConflictException("Hanya Purchase Order berstatus 'draft' yang dapat dipesan ke supplier.");
        }

        $role = $user['role'] ?? '';
        if ($role !== 'admin' && $role !== 'warehouse') {
            throw new ForbiddenException('Akses ditolak.');
        }

        $this->orderRepository->updatePurchaseOrderStatus($orderId, 'sent_to_supplier', (int) $user['id']);
    }

    /**
     * Memproses Goods Receipt (Penerimaan Barang PO)
     *
     * @param array{id: int|string, role?: string} $user
     * @param list<array{product_id: int, quantity: int}>|null $itemsReceived
     */
    public function receivePurchaseOrder(array $user, int $orderId, ?array $itemsReceived = null): void
    {
        $role = $user['role'] ?? '';
        if ($role !== 'warehouse' && $role !== 'admin') {
            throw new ForbiddenException('Hanya Staff Gudang dan Admin yang dapat memproses penerimaan barang (Goods Receipt).');
        }

        $this->orderRepository->processGoodsReceipt($orderId, (int) $user['id'], $itemsReceived);
    }

    /**
     * @param array{id: int|string, role?: string} $user
     */
    public function cancelPurchaseOrder(array $user, int $orderId): void
    {
        $po = $this->getPurchaseOrder($orderId);

        if ($po->status === 'goods_received' || $po->status === 'received') {
            throw new ConflictException('Pesanan pengadaan yang sudah diterima tidak dapat dibatalkan.');
        }

        $this->orderRepository->updatePurchaseOrderStatus($orderId, 'cancelled');
    }

    /**
     * Kontrak Endpoint API JSON (API-01):
     * GET /api/products/{sku}/availability
     *
     * @return array{sku: string, name: string, unit: string, total_stock: int, warehouses: list<array{warehouse_code: string, warehouse_name: string, quantity: int}>}
     */
    public function getProductAvailability(string $sku): array
    {
        $cleanSku = trim($sku);
        if ($cleanSku === '') {
            throw new ValidationException('SKU produk tidak boleh kosong.');
        }

        $data = $this->orderRepository->getProductAvailabilityBySku($cleanSku);
        if ($data === null) {
            throw new NotFoundException("Produk dengan SKU '{$cleanSku}' tidak ditemukan.");
        }

        return $data;
    }
}
