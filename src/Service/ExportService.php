<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\CategoryRepositoryInterface;
use App\Repository\CustomerRepositoryInterface;
use App\Repository\OrderRepositoryInterface;
use App\Repository\ProductRepositoryInterface;
use App\Repository\SupplierRepositoryInterface;
use App\Repository\WarehouseRepositoryInterface;

final class ExportService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private WarehouseRepositoryInterface $warehouseRepository,
        private SupplierRepositoryInterface $supplierRepository,
        private CustomerRepositoryInterface $customerRepository,
        private OrderRepositoryInterface $orderRepository
    ) {}

    public function exportCsv(string $type, string $delimiter = ','): void
    {
        if (!in_array($delimiter, [',', ';'], true)) {
            $delimiter = ',';
        }

        $filename = match ($type) {
            'products' => 'daftar_master_produk.csv',
            'categories' => 'daftar_kategori_produk.csv',
            'warehouses' => 'daftar_lokasi_gudang.csv',
            'suppliers' => 'daftar_supplier.csv',
            'customers' => 'daftar_customer.csv',
            'sales', 'so' => 'daftar_sales_orders.csv',
            'po' => 'daftar_purchase_orders.csv',
            'ledger' => 'kartu_stok_ledger_audit.csv',
            'catalog' => 'katalog_harga_produk.csv',
            'warehouse', 'stock' => 'laporan_stok_gudang.csv',
            default => 'ringkasan_stok_semua_gudang.csv',
        };

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        // Tulis UTF-8 BOM (\xEF\xBB\xBF) agar Microsoft Excel membaca karakter & koma dengan presisi
        fwrite($out, "\xEF\xBB\xBF");

        if ($type === 'products' || $type === 'catalog') {
            fputcsv($out, ['Kode SKU', 'Nama Produk', 'Kategori', 'Harga Beli (Rp)', 'Harga Jual (Rp)', 'Satuan', 'Batas Minimum', 'Stok JKT', 'Stok SBY', 'Stok BDG', 'Total Stok', 'Status Stok'], $delimiter);
            $products = $this->productRepository->findAllWithStock();
            foreach ($products as $p) {
                $status = $p->totalStock <= (int)($p->minStockThreshold / 2) ? 'Kritis' : ($p->totalStock <= $p->minStockThreshold ? 'Reorder' : 'Aman');
                fputcsv($out, [
                    $p->sku,
                    $p->name,
                    $p->categoryName ?? '-',
                    $p->purchasePrice,
                    $p->sellingPrice,
                    $p->unit,
                    $p->minStockThreshold,
                    $p->stockJkt,
                    $p->stockSby,
                    $p->stockBdg,
                    $p->totalStock,
                    $status
                ], $delimiter);
            }
        } elseif ($type === 'categories') {
            fputcsv($out, ['ID', 'Kode Kategori', 'Nama Kategori', 'Deskripsi'], $delimiter);
            $categories = $this->categoryRepository->findAll();
            foreach ($categories as $c) {
                fputcsv($out, [$c->id, $c->code, $c->name, $c->description ?? '-'], $delimiter);
            }
        } elseif ($type === 'warehouses') {
            fputcsv($out, ['ID', 'Kode Gudang', 'Nama Gudang', 'Kota / Wilayah', 'Alamat Lengkap', 'Status Operasional'], $delimiter);
            $warehouses = $this->warehouseRepository->findAll();
            foreach ($warehouses as $w) {
                fputcsv($out, [$w->id, $w->code, $w->name, $w->city, $w->address ?? '-', $w->isActive === 1 ? 'Aktif Beroperasi' : 'Nonaktif'], $delimiter);
            }
        } elseif ($type === 'suppliers') {
            fputcsv($out, ['ID', 'Kode Supplier', 'Nama Perusahaan', 'Contact Person', 'Nomor Telepon', 'Email', 'Alamat Operasional'], $delimiter);
            $suppliers = $this->supplierRepository->findAll();
            foreach ($suppliers as $s) {
                fputcsv($out, [$s->id, $s->code, $s->name, $s->contactPerson ?? '-', $s->phone ?? '-', $s->email ?? '-', $s->address ?? '-'], $delimiter);
            }
        } elseif ($type === 'customers') {
            fputcsv($out, ['ID', 'Kode Customer', 'Nama Customer', 'Contact Person', 'Nomor Telepon', 'Email', 'Alamat Pengiriman'], $delimiter);
            $customers = $this->customerRepository->findAll();
            foreach ($customers as $c) {
                fputcsv($out, [$c->id, $c->code, $c->name, $c->contactPerson ?? '-', $c->phone ?? '-', $c->email ?? '-', $c->address ?? '-'], $delimiter);
            }
        } elseif ($type === 'sales' || $type === 'so') {
            fputcsv($out, ['No. Order', 'Customer', 'Gudang Pengiriman', 'Total Nilai (Rp)', 'Status SO', 'Dibuat Oleh', 'Tanggal Pesanan', 'Catatan'], $delimiter);
            $salesOrders = $this->orderRepository->findAllSalesOrders();
            foreach ($salesOrders as $so) {
                fputcsv($out, [$so->soNumber, $so->customerName ?? '-', $so->warehouseName ?? '-', $so->totalAmount, $so->status, $so->creatorName ?? '-', $so->createdAt ?? '-', $so->notes ?? '-'], $delimiter);
            }
        } elseif ($type === 'po') {
            fputcsv($out, ['No. PO', 'Supplier', 'Gudang Tujuan', 'Total Nilai (Rp)', 'Status PO', 'Dibuat Oleh', 'Tanggal PO', 'Catatan'], $delimiter);
            $purchaseOrders = $this->orderRepository->findAllPurchaseOrders();
            foreach ($purchaseOrders as $po) {
                fputcsv($out, [$po->poNumber, $po->supplierName ?? '-', $po->warehouseName ?? '-', $po->totalAmount, $po->status, $po->creatorName ?? '-', $po->createdAt ?? '-', $po->notes ?? '-'], $delimiter);
            }
        } elseif ($type === 'ledger') {
            fputcsv($out, ['Waktu Transaksi', 'Gudang', 'SKU', 'Nama Produk', 'Tipe Mutasi', 'Perubahan Qty', 'Saldo Akhir', 'No. Referensi', 'Operator', 'Catatan'], $delimiter);
            $ledgers = $this->orderRepository->findAllStockLedger();
            foreach ($ledgers as $l) {
                fputcsv($out, [$l->createdAt ?? '-', $l->warehouseName ?? '-', $l->productSku ?? '-', $l->productName ?? '-', $l->movementType, $l->quantityDelta, $l->balanceAfter, $l->referenceDocNumber ?? '-', $l->userName ?? '-', $l->notes ?? '-'], $delimiter);
            }
        } else {
            fputcsv($out, ['Kode SKU', 'Nama Produk', 'Kategori', 'Gudang JKT', 'Gudang SBY', 'Gudang BDG', 'Total Stok', 'Batas Minimum', 'Status'], $delimiter);
            $products = $this->productRepository->findAllWithStock();
            foreach ($products as $p) {
                $status = $p->totalStock <= (int)($p->minStockThreshold / 2) ? 'Kritis' : ($p->totalStock <= $p->minStockThreshold ? 'Reorder' : 'Aman');
                fputcsv($out, [
                    $p->sku,
                    $p->name,
                    $p->categoryName ?? '-',
                    $p->stockJkt,
                    $p->stockSby,
                    $p->stockBdg,
                    $p->totalStock,
                    $p->minStockThreshold,
                    $status
                ], $delimiter);
            }
        }

        fclose($out);
        exit;
    }
}
