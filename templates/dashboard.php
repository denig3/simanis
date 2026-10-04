<?php
/** @var array{id: int, name: string, email: string} $user */
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$productsStockSummary = $productsStockSummary ?? [];
$salesOrdersList = $salesOrdersList ?? [];
$purchaseOrdersList = $purchaseOrdersList ?? [];
$stockLedgerList = $stockLedgerList ?? [];
$warehousesList = $warehousesList ?? [];
$categoriesList = $categoriesList ?? [];
$suppliersList = $suppliersList ?? [];
$customersList = $customersList ?? [];
$pendingSOCount = $pendingSOCount ?? count(array_filter($salesOrdersList, static fn($so) => $so['status'] === 'pending_approval'));
$waitingPOCount = $waitingPOCount ?? count(array_filter($purchaseOrdersList, static fn($po) => $po['status'] === 'sent_to_supplier'));
$criticalStockCount = $criticalStockCount ?? count(array_filter($productsStockSummary, static fn($p) => (int)$p['total_stock'] <= (int)$p['min_stock_threshold']));
$readyGICount = $readyGICount ?? count(array_filter($salesOrdersList, static fn($so) => $so['status'] === 'approved'));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= $escape($csrf) ?>">
    <title>Prototype — Inventory &amp; Order Management System</title>
    <link rel="stylesheet" href="/assets/app.css?v=<?= time() ?>">
    <script src="/assets/app.js?v=<?= time() ?>" defer></script>
</head>
<body class="dashboard-body">

    <!-- Top Header Navigation Bar -->
    <header class="main-header">
        <div class="header-container">
            <div class="brand-group">
                <button id="sidebar-toggle-btn" class="sidebar-toggle-btn" type="button" title="Buka/Tutup Sidebar Menu (Toggle Collapse)" aria-label="Toggle Sidebar Navigation">
                    <span class="toggle-icon">☰</span>
                </button>
                <div class="brand-icon">ST</div>
                <div class="brand-text">
                    <strong>STOKORA</strong>
                    <span class="sub-brand">Inventory &amp; Order System</span>
                </div>
            </div>

            <!-- Role Simulator Switcher -->
            <div class="role-simulator-container">
                <span class="simulator-label">Simulasi Peran Akses:</span>
                <div class="role-selector" id="role-selector">
                    <button type="button" class="role-btn active" data-role="admin">
                        <span class="role-icon">👑</span> Admin
                    </button>
                    <button type="button" class="role-btn" data-role="sales">
                        <span class="role-icon">💼</span> Sales Staff
                    </button>
                    <button type="button" class="role-btn" data-role="warehouse">
                        <span class="role-icon">📦</span> Staff Gudang
                    </button>
                </div>
            </div>

            <div class="user-profile-menu">
                <div class="user-badge">
                    <div class="user-avatar" id="user-avatar-initial">A</div>
                    <div class="user-info-text">
                        <strong id="user-name-display"><?= $escape($user['name']) ?></strong>
                        <span class="user-role-label" id="current-role-badge">Admin System</span>
                    </div>
                </div>
                <button id="logout-button" class="logout-btn" type="button" title="Keluar dari sesi">Keluar</button>
            </div>
        </div>
    </header>

    <!-- Main Workspace Layout -->
    <div class="app-workspace">

        <!-- Sidebar Navigation Menu -->
        <aside class="sidebar-nav">
            <div class="sidebar-header">
                <span class="nav-title">MENU UTAMA</span>
            </div>
            <nav class="nav-menu">
                <button type="button" class="nav-item active" data-tab="tab-dashboard" title="Ringkasan Dashboard">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Ringkasan Dashboard</span>
                </button>
                <button type="button" class="nav-item role-restricted" data-tab="tab-users" data-allowed="admin" title="Manajemen User">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Manajemen User</span>
                </button>
                <!-- Master Data: label & konten berubah per peran via JS -->
                <button type="button" class="nav-item" id="nav-master" data-tab="tab-master" title="Master Data">
                    <span class="nav-icon" id="nav-master-icon">🗄️</span>
                    <span class="nav-text" id="nav-master-text">Master Data &amp; Gudang</span>
                </button>
                <button type="button" class="nav-item" data-tab="tab-sales-orders" title="Sales Orders (SO)">
                    <span class="nav-icon">🛒</span>
                    <span class="nav-text">Sales Orders (SO)</span>
                    <span class="badge badge-warning" id="pending-so-count"><?= (int)$pendingSOCount ?></span>
                </button>
                <!-- Tab PO: disembunyikan untuk peran Sales via JS -->
                <button type="button" class="nav-item" id="nav-po" data-tab="tab-purchase-orders" title="Purchase Orders &amp; Goods Receipt">
                    <span class="nav-icon">🚚</span>
                    <span class="nav-text">Purchase Orders &amp; GR</span>
                </button>
                <button type="button" class="nav-item" data-tab="tab-stock-ledger" title="Stock Ledger Multigudang">
                    <span class="nav-icon">📜</span>
                    <span class="nav-text">Stock Ledger Multigudang</span>
                </button>
            </nav>

            <div class="sidebar-footer">
                <div class="segregation-notice">
                    <span class="notice-icon">🛡️</span>
                    <div class="notice-text">
                        <strong>Prinsip 3 Peran (SOD)</strong>
                        <small>Pembuat transaksi tidak dapat menyetujui transaksi sendiri.</small>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Panel Area -->
        <main class="content-area">

            <!-- Global Alert Container -->
            <div id="form-message" class="form-message" role="alert" hidden></div>
            <div id="prototype-toast" class="toast-notification" hidden></div>

            <!-- TAB 1: RINGKASAN DASHBOARD — konten berbeda per peran -->
            <section id="tab-dashboard" class="tab-panel active">
                <div class="section-title-bar">
                    <div>
                        <h2 id="dash-title">Ringkasan Dashboard — Admin</h2>
                        <p class="muted" id="dash-subtitle">Gambaran umum seluruh data produk, stok, dan status pesanan berjalan.</p>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <a href="/erd.html" target="_blank" class="btn btn-secondary" style="text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem;" title="Buka ERD Interaktif Database">📐 Diagram ERD</a>
                        <button type="button" class="btn btn-secondary" id="btn-flow-modal">💡 Alur Operasional</button>
                    </div>
                </div>

                <!-- === ADMIN VIEW: Seluruh data === -->
                <div id="dash-view-admin" class="role-dash-view">
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-icon bg-emerald">📦</div>
                            <div class="metric-data">
                                <span class="metric-label">Total Jenis Produk</span>
                                <strong class="metric-value"><?= count($productsStockSummary) ?> SKU</strong>
                                <small class="text-success">Terdistribusi di <?= count($warehousesList) ?> Gudang</small>
                            </div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-icon bg-blue">🏪</div>
                            <div class="metric-data">
                                <span class="metric-label">Gudang Aktif</span>
                                <strong class="metric-value"><?= count($warehousesList) ?> Gudang</strong>
                                <small class="muted">Jakarta, Surabaya, Bandung</small>
                            </div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-icon bg-amber">⏳</div>
                            <div class="metric-data">
                                <span class="metric-label">SO Pending Approval</span>
                                <strong class="metric-value" id="dash-pending-so"><?= (int)$pendingSOCount ?> Order</strong>
                                <small class="text-amber">Butuh Peninjauan Admin</small>
                            </div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-icon bg-purple">🚚</div>
                            <div class="metric-data">
                                <span class="metric-label">PO Menunggu Barang</span>
                                <strong class="metric-value"><?= (int)$waitingPOCount ?> Supplier PO</strong>
                                <small class="text-blue">Siap Goods Receipt</small>
                            </div>
                        </div>
                    </div>
                    <div class="panel-card">
                        <div class="card-header flex-between">
                            <div>
                                <h3>📊 Status Stok Per Gudang — Semua Produk</h3>
                                <span class="card-subtitle">Real-time Stock Ledger • Data langsung dari Database</span>
                            </div>
                            <a href="/export/csv?type=admin" download="ringkasan_stok_semua_gudang.csv" class="btn btn-secondary btn-sm" id="btn-export-dash-admin">📥 Ekspor Semua Data (CSV)</a>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="table-dash-admin-stock">
                                <thead>
                                    <tr>
                                        <th>Kode SKU</th><th>Nama Produk</th>
                                        <th>Gudang JKT</th><th>Gudang SBY</th><th>Gudang BDG</th>
                                        <th>Total Stok</th><th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($productsStockSummary)): ?>
                                        <?php foreach ($productsStockSummary as $prod): ?>
                                            <?php
                                                $tStock = (int) $prod['total_stock'];
                                                $minStock = (int) $prod['min_stock_threshold'];
                                                if ($tStock <= (int)($minStock / 2)) {
                                                    $badgeClass = 'badge-danger';
                                                    $statusText = 'Stok Rendah';
                                                } elseif ($tStock <= $minStock) {
                                                    $badgeClass = 'badge-warning';
                                                    $statusText = 'Reorder Needed';
                                                } else {
                                                    $badgeClass = 'badge-success';
                                                    $statusText = 'Aman';
                                                }
                                            ?>
                                            <tr>
                                                <td><code><?= $escape((string)$prod['sku']) ?></code></td>
                                                <td><strong><?= $escape((string)$prod['name']) ?></strong></td>
                                                <td><?= (int)$prod['stock_jkt'] ?> unit</td>
                                                <td><?= (int)$prod['stock_sby'] ?> unit</td>
                                                <td><?= (int)$prod['stock_bdg'] ?> unit</td>
                                                <td><strong><?= $tStock ?> unit</strong></td>
                                                <td><span class="badge <?= $badgeClass ?>"><?= $escape($statusText) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center text-muted">Belum ada data produk di database.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div><!-- /dash-view-admin -->

                <!-- === SALES VIEW: Ringkasan order miliknya === -->
                <div id="dash-view-sales" class="role-dash-view" hidden>
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-icon bg-amber">📝</div>
                            <div class="metric-data">
                                <span class="metric-label">SO Draft Saya</span>
                                <strong class="metric-value"><?= count(array_filter($salesOrdersList, static fn($so) => $so['status'] === 'draft')) ?> Order</strong>
                                <small class="text-amber">Belum Diajukan</small>
                            </div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-icon bg-blue">⏳</div>
                            <div class="metric-data">
                                <span class="metric-label">Menunggu Persetujuan</span>
                                <strong class="metric-value"><?= (int)$pendingSOCount ?> Order</strong>
                                <small class="text-blue">Pending Admin Review</small>
                            </div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-icon bg-emerald">✅</div>
                            <div class="metric-data">
                                <span class="metric-label">SO Disetujui</span>
                                <strong class="metric-value"><?= (int)$readyGICount ?> Order</strong>
                                <small class="text-success">Diproses gudang</small>
                            </div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-icon bg-purple">🎉</div>
                            <div class="metric-data">
                                <span class="metric-label">Fulfilled Bulan Ini</span>
                                <strong class="metric-value"><?= count(array_filter($salesOrdersList, static fn($so) => $so['status'] === 'fulfilled')) ?> Order</strong>
                                <small class="muted">Telah selesai</small>
                            </div>
                        </div>
                    </div>
                    <div class="panel-card">
                        <div class="card-header flex-between">
                            <div>
                                <h3>🛒 Sales Order Milik Saya</h3>
                                <span class="card-subtitle">Data langsung dari Database</span>
                            </div>
                            <a href="/export/csv?type=sales" download="daftar_sales_orders.csv" class="btn btn-secondary btn-sm" id="btn-export-dash-sales">📥 Ekspor Order Saya (CSV)</a>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="table-dash-sales-orders">
                                <thead>
                                    <tr><th>No. SO</th><th>Customer</th><th>Item &amp; Qty</th><th>Nilai Order</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($salesOrdersList)): ?>
                                        <?php foreach ($salesOrdersList as $so): ?>
                                            <?php
                                                $badgeMap = [
                                                    'draft' => 'badge-secondary',
                                                    'pending_approval' => 'badge-warning',
                                                    'approved' => 'badge-blue',
                                                    'rejected' => 'badge-danger',
                                                    'fulfilled' => 'badge-success',
                                                    'cancelled' => 'badge-secondary'
                                                ];
                                                $bClass = $badgeMap[$so['status']] ?? 'badge-secondary';
                                            ?>
                                            <tr>
                                                <td><strong>#<?= $escape((string)$so['so_number']) ?></strong></td>
                                                <td><?= $escape((string)$so['customer_name']) ?></td>
                                                <td><?= $escape((string)$so['items_summary']) ?></td>
                                                <td>Rp <?= number_format((float)$so['total_amount'], 0, ',', '.') ?></td>
                                                <td><span class="badge <?= $bClass ?>"><?= $escape((string)$so['status']) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center text-muted">Belum ada data Sales Order.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div><!-- /dash-view-sales -->

                <!-- === WAREHOUSE VIEW: Stok & fulfillment === -->
                <div id="dash-view-warehouse" class="role-dash-view" hidden>
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-icon bg-amber">📦</div>
                            <div class="metric-data">
                                <span class="metric-label">SO Siap Goods Issue</span>
                                <strong class="metric-value"><?= (int)$readyGICount ?> Order</strong>
                                <small class="text-amber">Perlu Diproses Segera</small>
                            </div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-icon bg-blue">🚚</div>
                            <div class="metric-data">
                                <span class="metric-label">PO Siap Goods Receipt</span>
                                <strong class="metric-value"><?= (int)$waitingPOCount ?> PO</strong>
                                <small class="text-blue">Barang dari Supplier</small>
                            </div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-icon bg-emerald">✅</div>
                            <div class="metric-data">
                                <span class="metric-label">Total Transaksi SO</span>
                                <strong class="metric-value"><?= count($salesOrdersList) ?> Order</strong>
                                <small class="text-success">Tercatat di Sistem</small>
                            </div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-icon bg-purple">⚠️</div>
                            <div class="metric-data">
                                <span class="metric-label">Produk Stok Kritis</span>
                                <strong class="metric-value"><?= (int)$criticalStockCount ?> SKU</strong>
                                <small class="text-danger">Di bawah batas minimum</small>
                            </div>
                        </div>
                    </div>
                    <div class="panel-card">
                        <div class="card-header flex-between">
                            <div>
                                <h3>📦 Antrian Goods Issue — SO Disetujui</h3>
                                <span class="card-subtitle">SO berstatus Approved siap diproses pengiriman stok</span>
                            </div>
                            <a href="/export/csv?type=warehouse" download="laporan_stok_gudang.csv" class="btn btn-secondary btn-sm" id="btn-export-dash-warehouse">📥 Ekspor Laporan Stok (CSV)</a>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="table-dash-warehouse-gi">
                                <thead>
                                    <tr><th>No. SO</th><th>Customer</th><th>Gudang Asal</th><th>Item &amp; Qty</th><th>Status</th><th>Aksi Gudang</th></tr>
                                </thead>
                                <tbody>
                                    <?php 
                                        $approvedList = array_filter($salesOrdersList, static fn($so) => $so['status'] === 'approved');
                                    ?>
                                    <?php if (!empty($approvedList)): ?>
                                        <?php foreach ($approvedList as $so): ?>
                                            <tr>
                                                <td><strong>#<?= $escape((string)$so['so_number']) ?></strong></td>
                                                <td><?= $escape((string)$so['customer_name']) ?></td>
                                                <td><?= $escape((string)$so['warehouse_name']) ?></td>
                                                <td><?= $escape((string)$so['items_summary']) ?></td>
                                                <td><span class="badge badge-blue">Approved</span></td>
                                                <td><button class="btn btn-primary btn-sm" onclick="SimulasiModule.processGoodsIssue('<?= (int)$so['id'] ?>')">📦 Proses Goods Issue</button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center text-muted">Tidak ada antrian Goods Issue yang menunggu.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="panel-card">
                        <div class="card-header">
                            <h3>⚠️ Peringatan Stok Kritis</h3>
                            <span class="card-subtitle">Produk di bawah batas minimum — segera usulkan Purchase Order</span>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr><th>SKU</th><th>Produk</th><th>Stok Saat Ini</th><th>Batas Min.</th><th>Aksi</th></tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $critList = array_filter($productsStockSummary, static fn($p) => (int)$p['total_stock'] <= (int)$p['min_stock_threshold']);
                                    ?>
                                    <?php if (!empty($critList)): ?>
                                        <?php foreach ($critList as $cp): ?>
                                            <tr>
                                                <td><code><?= $escape((string)$cp['sku']) ?></code></td>
                                                <td><strong><?= $escape((string)$cp['name']) ?></strong></td>
                                                <td><strong class="text-danger"><?= (int)$cp['total_stock'] ?> unit</strong></td>
                                                <td><?= (int)$cp['min_stock_threshold'] ?> unit</td>
                                                <td><button class="btn btn-secondary btn-sm" onclick="document.querySelector('#nav-po').click()">📋 Usulkan PO</button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center text-muted">Semua stok produk berada di atas batas aman.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div><!-- /dash-view-warehouse -->
            </section>

            <!-- TAB 2: MANAJEMEN USER (ADMIN ONLY) -->
            <section id="tab-users" class="tab-panel">
                <div class="section-title-bar">
                    <div>
                        <h2>Manajemen User &amp; Hak Akses Peran</h2>
                        <p class="muted">Kelola akun pengguna dan pembagian peran (Admin, Sales, Staff Gudang).</p>
                    </div>
                    <button type="button" class="btn btn-primary" id="btn-open-add-user">+ Tambah User Baru</button>
                </div>

                <div class="panel-card">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Pengguna</th>
                                    <th>Email</th>
                                    <th>Peran (Role)</th>
                                    <th>Gudang Penugasan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body">
                                <?php if (!empty($usersList)): ?>
                                    <?php foreach ($usersList as $u): ?>
                                        <?php
                                            $uRawId = (int) $u['id'];
                                            $uCode = '#USR-' . str_pad((string) $u['id'], 2, '0', STR_PAD_LEFT);
                                            $uRole = (string) $u['role'];
                                            $uRoleBadge = $uRole === 'admin' ? 'badge-purple' : ($uRole === 'sales' ? 'badge-blue' : 'badge-warning');
                                            $uRoleTitle = $uRole === 'admin' ? 'Admin' : ($uRole === 'sales' ? 'Sales Staff' : 'Warehouse Staff');
                                            $uWarehouse = $u['warehouse_name'] ?? 'Semua Gudang (Pusat)';
                                            $rawStatus = (string) $u['status'];
                                            $uStatusText = strtolower($rawStatus) === 'active' ? 'Aktif' : 'Nonaktif';
                                            $uStatusBadge = strtolower($rawStatus) === 'active' ? 'badge-success' : 'badge-danger';
                                            $isSelf = (int) $u['id'] === (int) ($user['id'] ?? 0);
                                        ?>
                                        <tr id="user-row-<?= $uRawId ?>"
                                            data-user-id="<?= $uRawId ?>"
                                            data-user-code="<?= $escape($uCode) ?>"
                                            data-user-name="<?= $escape((string)$u['name']) ?>"
                                            data-user-email="<?= $escape((string)$u['email']) ?>"
                                            data-user-role="<?= $escape($uRole) ?>"
                                            data-user-warehouse="<?= $escape((string)$uWarehouse) ?>"
                                            data-user-status="<?= $escape($rawStatus) ?>">
                                            <td><?= $escape($uCode) ?></td>
                                            <td class="user-cell-name"><strong><?= $escape((string) $u['name']) ?></strong></td>
                                            <td class="user-cell-email"><?= $escape((string) $u['email']) ?></td>
                                            <td class="user-cell-role"><span class="badge <?= $uRoleBadge ?>"><?= $escape($uRoleTitle) ?></span></td>
                                            <td class="user-cell-warehouse"><?= $escape((string) $uWarehouse) ?></td>
                                            <td class="user-cell-status"><span class="badge <?= $uStatusBadge ?>"><?= $escape($uStatusText) ?></span></td>
                                            <td class="user-cell-action">
                                                <?php if ($isSelf): ?>
                                                    <button type="button" class="btn-sm btn-secondary" disabled title="Akun Anda yang sedang aktif">Utama (Anda)</button>
                                                <?php else: ?>
                                                    <button type="button" class="btn-sm btn-secondary btn-edit-user" data-user-id="<?= $uRawId ?>">✏️ Edit Role</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td>#USR-01</td>
                                        <td><strong>Administrator Utama</strong></td>
                                        <td>admin@example.com</td>
                                        <td><span class="badge badge-purple">Admin</span></td>
                                        <td>Semua Gudang (Pusat)</td>
                                        <td><span class="badge badge-success">Aktif</span></td>
                                        <td><button class="btn-sm btn-secondary" disabled>Utama</button></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- TAB 3: MASTER DATA — Disesuaikan per Peran -->
            <section id="tab-master" class="tab-panel">
                <!-- === ADMIN MASTER DATA VIEW === -->
                <div id="master-view-admin" class="role-master-view">
                    <div class="section-title-bar">
                        <div>
                            <h2>Master Data Management (Admin)</h2>
                            <p class="muted">Pengelolaan Produk, Kategori, Multigudang, Supplier, dan Customer.</p>
                        </div>
                    </div>

                    <div class="sub-tab-nav">
                        <button type="button" class="sub-tab-btn active" data-subtab="subtab-products">📦 Master Produk <span class="badge badge-neutral" id="badge-count-products"><?= count($productsStockSummary) ?></span></button>
                        <button type="button" class="sub-tab-btn" data-subtab="subtab-categories">📁 Kategori <span class="badge badge-neutral" id="badge-count-categories"><?= count($categoriesList) ?></span></button>
                        <button type="button" class="sub-tab-btn" data-subtab="subtab-warehouses">🏪 Lokasi Gudang <span class="badge badge-neutral" id="badge-count-warehouses"><?= count($warehousesList) ?></span></button>
                        <button type="button" class="sub-tab-btn" data-subtab="subtab-suppliers">🏭 Supplier <span class="badge badge-neutral" id="badge-count-suppliers"><?= count($suppliersList) ?></span></button>
                        <button type="button" class="sub-tab-btn" data-subtab="subtab-customers">👤 Customer <span class="badge badge-neutral" id="badge-count-customers"><?= count($customersList) ?></span></button>
                    </div>

                    <!-- SUB-TAB 1: MASTER PRODUK -->
                    <div id="subtab-products" class="sub-tab-content">
                        <div class="panel-card">
                            <div class="card-header flex-between">
                                <div>
                                    <h3>Daftar Produk &amp; Lokasi Gudang</h3>
                                    <span class="card-subtitle">Master data barang per September 2026</span>
                                </div>
                                <div style="display:flex;gap:0.5rem;align-items:center;">
                                    <a href="/export/csv?type=products" download="daftar_master_produk.csv" class="btn btn-secondary btn-sm" id="btn-export-products">📥 Ekspor Produk (CSV)</a>
                                    <button type="button" class="btn btn-primary btn-sm" id="btn-open-add-product">+ Tambah Produk</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="data-table" id="table-master-products">
                                    <thead>
                                        <tr>
                                             <th>SKU</th>
                                            <th>Nama Barang</th>
                                            <th>Kategori</th>
                                            <th>Harga Jual</th>
                                            <th>Batas Minimum</th>
                                            <th>Status Stok Total</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="products-table-body">
                                        <?php if (!empty($productsStockSummary)): ?>
                                            <?php foreach ($productsStockSummary as $prod): ?>
                                                <?php
                                                    $pId = (int) $prod['id'];
                                                    $tStock = (int) $prod['total_stock'];
                                                    $minStock = (int) $prod['min_stock_threshold'];
                                                    $isActive = (int) ($prod['is_active'] ?? 1);
                                                    $bClass = $tStock <= (int)($minStock / 2) ? 'badge-danger' : ($tStock <= $minStock ? 'badge-warning' : 'badge-success');
                                                    $sText = $tStock <= (int)($minStock / 2) ? 'Kritis' : ($tStock <= $minStock ? 'Reorder' : 'Aman');
                                                ?>
                                                <tr id="prod-row-<?= $pId ?>"
                                                    data-id="<?= $pId ?>"
                                                    data-sku="<?= $escape((string)$prod['sku']) ?>"
                                                    data-name="<?= $escape((string)$prod['name']) ?>"
                                                    data-category-id="<?= (int)($prod['category_id'] ?? 0) ?>"
                                                    data-category-name="<?= $escape((string)($prod['category_name'] ?? '-')) ?>"
                                                    data-purchase-price="<?= (float)$prod['purchase_price'] ?>"
                                                    data-selling-price="<?= (float)$prod['selling_price'] ?>"
                                                    data-unit="<?= $escape((string)$prod['unit']) ?>"
                                                    data-min-stock="<?= $minStock ?>"
                                                    data-is-active="<?= $isActive ?>">
                                                    <td class="cell-sku"><code><?= $escape((string)$prod['sku']) ?></code></td>
                                                    <td class="cell-name"><strong><?= $escape((string)$prod['name']) ?></strong></td>
                                                    <td class="cell-category"><?= $escape((string)($prod['category_name'] ?? 'Elektronik')) ?></td>
                                                    <td class="cell-selling-price">Rp <?= number_format((float)$prod['selling_price'], 0, ',', '.') ?></td>
                                                    <td class="cell-min-stock"><?= $minStock ?> <?= $escape((string)$prod['unit']) ?></td>
                                                    <td class="cell-total-stock"><?= $tStock ?> <?= $escape((string)$prod['unit']) ?> (<span class="badge <?= $bClass ?>"><?= $escape($sText) ?></span>)</td>
                                                    <td class="cell-action" style="white-space: nowrap;">
                                                        <button type="button" class="btn-sm btn-secondary btn-edit-product" data-id="<?= $pId ?>">✏️ Edit</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr id="empty-products-row"><td colspan="7" class="text-center text-muted">Belum ada master produk.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div><!-- /subtab-products -->

                    <!-- SUB-TAB 2: KATEGORI PRODUK -->
                    <div id="subtab-categories" class="sub-tab-content" hidden>
                        <div class="panel-card">
                            <div class="card-header flex-between">
                                <div>
                                    <h3>📁 Master Kategori Produk</h3>
                                    <span class="card-subtitle">Klasifikasi barang dan penentuan hierarki produk</span>
                                </div>
                                <div style="display:flex;gap:0.5rem;align-items:center;">
                                    <a href="/export/csv?type=categories" download="daftar_kategori_produk.csv" class="btn btn-secondary btn-sm" id="btn-export-categories">📥 Ekspor Kategori (CSV)</a>
                                    <button type="button" class="btn btn-primary btn-sm" id="btn-open-add-category">+ Tambah Kategori</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="data-table" id="table-master-categories">
                                    <thead>
                                        <tr>
                                            <th>Kode</th>
                                            <th>Nama Kategori</th>
                                            <th>Deskripsi</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="categories-table-body">
                                        <?php if (!empty($categoriesList)): ?>
                                            <?php foreach ($categoriesList as $cat): ?>
                                                <?php $cId = (int)$cat['id']; ?>
                                                <tr id="cat-row-<?= $cId ?>"
                                                    data-id="<?= $cId ?>"
                                                    data-code="<?= $escape((string)$cat['code']) ?>"
                                                    data-name="<?= $escape((string)$cat['name']) ?>"
                                                    data-description="<?= $escape((string)($cat['description'] ?? '')) ?>">
                                                    <td class="cell-code"><code><?= $escape((string)$cat['code']) ?></code></td>
                                                    <td class="cell-name"><strong><?= $escape((string)$cat['name']) ?></strong></td>
                                                    <td class="cell-description"><?= $escape((string)($cat['description'] ?? '-')) ?></td>
                                                    <td><span class="badge badge-success">Aktif</span></td>
                                                    <td class="cell-action" style="white-space: nowrap;">
                                                        <button type="button" class="btn-sm btn-secondary btn-edit-category" data-id="<?= $cId ?>">✏️ Edit</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr id="empty-categories-row"><td colspan="5" class="text-center text-muted">Belum ada kategori terdaftar.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div><!-- /subtab-categories -->

                    <!-- SUB-TAB 3: LOKASI MULTIGUDANG -->
                    <div id="subtab-warehouses" class="sub-tab-content" hidden>
                        <div class="panel-card">
                            <div class="card-header flex-between">
                                <div>
                                    <h3>🏪 Lokasi Multigudang &amp; Cabang</h3>
                                    <span class="card-subtitle">Pusat distribusi logistik, cabang operasional, dan alokasi stok fisik</span>
                                </div>
                                <div style="display:flex;gap:0.5rem;align-items:center;">
                                    <a href="/export/csv?type=warehouses" download="daftar_lokasi_gudang.csv" class="btn btn-secondary btn-sm" id="btn-export-warehouses">📥 Ekspor Gudang (CSV)</a>
                                    <button type="button" class="btn btn-primary btn-sm" id="btn-open-add-warehouse">+ Tambah Gudang</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="data-table" id="table-master-warehouses">
                                    <thead>
                                        <tr>
                                            <th>Kode Gudang</th>
                                            <th>Nama Gudang</th>
                                            <th>Kota / Wilayah</th>
                                            <th>Alamat Lengkap</th>
                                            <th>Status Operasional</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="warehouses-table-body">
                                        <?php if (!empty($warehousesList)): ?>
                                            <?php foreach ($warehousesList as $wh): ?>
                                                <?php 
                                                    $whId = (int)$wh['id'];
                                                    $isWhActive = (int)($wh['is_active'] ?? 1) === 1; 
                                                ?>
                                                <tr id="wh-row-<?= $whId ?>"
                                                    data-id="<?= $whId ?>"
                                                    data-code="<?= $escape((string)$wh['code']) ?>"
                                                    data-name="<?= $escape((string)$wh['name']) ?>"
                                                    data-city="<?= $escape((string)$wh['city']) ?>"
                                                    data-address="<?= $escape((string)($wh['address'] ?? '')) ?>"
                                                    data-is-active="<?= $isWhActive ? 1 : 0 ?>">
                                                    <td class="cell-code"><code><?= $escape((string)$wh['code']) ?></code></td>
                                                    <td class="cell-name"><strong><?= $escape((string)$wh['name']) ?></strong></td>
                                                    <td class="cell-city"><?= $escape((string)$wh['city']) ?></td>
                                                    <td class="cell-address"><?= $escape((string)($wh['address'] ?? '-')) ?></td>
                                                    <td class="cell-status"><span class="badge <?= $isWhActive ? 'badge-success' : 'badge-danger' ?>"><?= $isWhActive ? 'Aktif Beroperasi' : 'Nonaktif' ?></span></td>
                                                    <td class="cell-action" style="white-space: nowrap;">
                                                        <button type="button" class="btn-sm btn-secondary btn-edit-warehouse" data-id="<?= $whId ?>">✏️ Edit</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr id="empty-warehouses-row"><td colspan="6" class="text-center text-muted">Belum ada lokasi gudang terdaftar.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div><!-- /subtab-warehouses -->

                    <!-- SUB-TAB 4: DAFTAR SUPPLIER -->
                    <div id="subtab-suppliers" class="sub-tab-content" hidden>
                        <div class="panel-card">
                            <div class="card-header flex-between">
                                <div>
                                    <h3>🏭 Daftar Pemasok / Supplier</h3>
                                    <span class="card-subtitle">Mitra resmi pengadaan barang masuk dan Purchase Order (PO)</span>
                                </div>
                                <div style="display:flex;gap:0.5rem;align-items:center;">
                                    <a href="/export/csv?type=suppliers" download="daftar_supplier.csv" class="btn btn-secondary btn-sm" id="btn-export-suppliers">📥 Ekspor Supplier (CSV)</a>
                                    <button type="button" class="btn btn-primary btn-sm" id="btn-open-add-supplier">+ Tambah Supplier</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="data-table" id="table-master-suppliers">
                                    <thead>
                                        <tr>
                                            <th>Kode</th>
                                            <th>Nama Perusahaan</th>
                                            <th>Contact Person</th>
                                            <th>Nomor Telepon</th>
                                            <th>Email</th>
                                            <th>Alamat Operasional</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="suppliers-table-body">
                                        <?php if (!empty($suppliersList)): ?>
                                            <?php foreach ($suppliersList as $sup): ?>
                                                <?php $supId = (int)$sup['id']; ?>
                                                <tr id="sup-row-<?= $supId ?>"
                                                    data-id="<?= $supId ?>"
                                                    data-code="<?= $escape((string)$sup['code']) ?>"
                                                    data-name="<?= $escape((string)$sup['name']) ?>"
                                                    data-contact="<?= $escape((string)($sup['contact_person'] ?? '')) ?>"
                                                    data-phone="<?= $escape((string)($sup['phone'] ?? '')) ?>"
                                                    data-email="<?= $escape((string)($sup['email'] ?? '')) ?>"
                                                    data-address="<?= $escape((string)($sup['address'] ?? '')) ?>">
                                                    <td class="cell-code"><code><?= $escape((string)$sup['code']) ?></code></td>
                                                    <td class="cell-name"><strong><?= $escape((string)$sup['name']) ?></strong></td>
                                                    <td class="cell-contact"><?= $escape((string)($sup['contact_person'] ?? '-')) ?></td>
                                                    <td class="cell-phone"><?= $escape((string)($sup['phone'] ?? '-')) ?></td>
                                                    <td class="cell-email"><?= $escape((string)($sup['email'] ?? '-')) ?></td>
                                                    <td class="cell-address"><?= $escape((string)($sup['address'] ?? '-')) ?></td>
                                                    <td class="cell-action" style="white-space: nowrap;">
                                                        <button type="button" class="btn-sm btn-secondary btn-edit-supplier" data-id="<?= $supId ?>">✏️ Edit</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr id="empty-suppliers-row"><td colspan="7" class="text-center text-muted">Belum ada data supplier terdaftar.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div><!-- /subtab-suppliers -->

                    <!-- SUB-TAB 5: DAFTAR CUSTOMER -->
                    <div id="subtab-customers" class="sub-tab-content" hidden>
                        <div class="panel-card">
                            <div class="card-header flex-between">
                                <div>
                                    <h3>👤 Daftar Pelanggan / Customer (B2B)</h3>
                                    <span class="card-subtitle">Klien pemesan barang untuk penerbitan Sales Order &amp; Delivery</span>
                                </div>
                                <div style="display:flex;gap:0.5rem;align-items:center;">
                                    <a href="/export/csv?type=customers" download="daftar_customer.csv" class="btn btn-secondary btn-sm" id="btn-export-customers">📥 Ekspor Customer (CSV)</a>
                                    <button type="button" class="btn btn-primary btn-sm" id="btn-open-add-customer">+ Tambah Customer</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="data-table" id="table-master-customers">
                                    <thead>
                                        <tr>
                                            <th>Kode</th>
                                            <th>Nama Customer</th>
                                            <th>Contact Person</th>
                                            <th>Nomor Telepon</th>
                                            <th>Email</th>
                                            <th>Alamat Pengiriman</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="customers-table-body">
                                        <?php if (!empty($customersList)): ?>
                                            <?php foreach ($customersList as $cust): ?>
                                                <?php $custId = (int)$cust['id']; ?>
                                                <tr id="cust-row-<?= $custId ?>"
                                                    data-id="<?= $custId ?>"
                                                    data-code="<?= $escape((string)$cust['code']) ?>"
                                                    data-name="<?= $escape((string)$cust['name']) ?>"
                                                    data-contact="<?= $escape((string)($cust['contact_person'] ?? '')) ?>"
                                                    data-phone="<?= $escape((string)($cust['phone'] ?? '')) ?>"
                                                    data-email="<?= $escape((string)($cust['email'] ?? '')) ?>"
                                                    data-address="<?= $escape((string)($cust['address'] ?? '')) ?>">
                                                    <td class="cell-code"><code><?= $escape((string)$cust['code']) ?></code></td>
                                                    <td class="cell-name"><strong><?= $escape((string)$cust['name']) ?></strong></td>
                                                    <td class="cell-contact"><?= $escape((string)($cust['contact_person'] ?? '-')) ?></td>
                                                    <td class="cell-phone"><?= $escape((string)($cust['phone'] ?? '-')) ?></td>
                                                    <td class="cell-email"><?= $escape((string)($cust['email'] ?? '-')) ?></td>
                                                    <td class="cell-address"><?= $escape((string)($cust['address'] ?? '-')) ?></td>
                                                    <td class="cell-action" style="white-space: nowrap;">
                                                        <button type="button" class="btn-sm btn-secondary btn-edit-customer" data-id="<?= $custId ?>">✏️ Edit</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr id="empty-customers-row"><td colspan="7" class="text-center text-muted">Belum ada customer terdaftar.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div><!-- /subtab-customers -->
                </div><!-- /master-view-admin -->

                <!-- === SALES MASTER DATA VIEW: Katalog Produk (Read-Only) === -->
                <div id="master-view-sales" class="role-master-view" hidden>
                    <div class="section-title-bar">
                        <div>
                            <h2>Katalog Produk &amp; Harga Resmi</h2>
                            <p class="muted">Referensi produk aktif dan harga jual untuk pembuatan penawaran atau Sales Order (Read-only).</p>
                        </div>
                        <a href="/export/csv?type=catalog" download="katalog_harga_produk.csv" class="btn btn-secondary" id="btn-export-catalog">📥 Ekspor Katalog (CSV)</a>
                    </div>

                    <div class="panel-card">
                        <div class="card-header flex-between">
                            <div>
                                <h3>📖 Daftar Produk Tersedia</h3>
                                <span class="card-subtitle">Harga resmi berlaku per September 2026</span>
                            </div>
                            <span class="badge badge-blue">🔒 Akses Sales: Read-Only</span>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="table-catalog-products">
                                <thead>
                                    <tr>
                                        <th>Kode SKU</th>
                                        <th>Nama Produk</th>
                                        <th>Kategori</th>
                                        <th>Harga Jual Satuan</th>
                                        <th>Ketersediaan Stok</th>
                                        <th>Aksi Cepat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($productsStockSummary)): ?>
                                        <?php foreach ($productsStockSummary as $prod): ?>
                                            <?php
                                                $tStock = (int) $prod['total_stock'];
                                                $stockLabel = $tStock > 0 ? "Tersedia ({$tStock} unit)" : 'Habis';
                                                $bClass = $tStock > 0 ? 'badge-success' : 'badge-danger';
                                            ?>
                                            <tr>
                                                <td><code><?= $escape((string)$prod['sku']) ?></code></td>
                                                <td><strong><?= $escape((string)$prod['name']) ?></strong></td>
                                                <td><?= $escape((string)($prod['category_name'] ?? 'Elektronik')) ?></td>
                                                <td><strong>Rp <?= number_format((float)$prod['selling_price'], 0, ',', '.') ?></strong></td>
                                                <td><span class="badge <?= $bClass ?>"><?= $escape($stockLabel) ?></span></td>
                                                <td><button class="btn-sm btn-primary" onclick="document.querySelector('.nav-item[data-tab=\'tab-sales-orders\']').click(); SimulasiModule.createSalesOrder();">➕ Buat SO</button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center text-muted">Belum ada katalog produk.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div><!-- /master-view-sales -->

                <!-- === WAREHOUSE MASTER DATA VIEW: Stok & Lokasi Gudang (Read-Only) === -->
                <div id="master-view-warehouse" class="role-master-view" hidden>
                    <div class="section-title-bar">
                        <div>
                            <h2>Katalog Produk &amp; Alokasi Stok Gudang</h2>
                            <p class="muted">Monitoring ketersediaan fisik barang di Gudang Jakarta, Surabaya, dan Bandung (Read-only).</p>
                        </div>
                        <a href="/export/csv?type=warehouse" download="laporan_stok_gudang.csv" class="btn btn-secondary" id="btn-export-warehouse-stock">📥 Ekspor Stok (CSV)</a>
                    </div>

                    <div class="panel-card">
                        <div class="card-header flex-between">
                            <div>
                                <h3>🏬 Ketersediaan Stok Per Gudang</h3>
                                <span class="card-subtitle">Staff Gudang tidak dapat merubah data master harga atau nama produk</span>
                            </div>
                            <span class="badge badge-warning">🔒 Akses Gudang: Read-Only</span>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="table-warehouse-stock">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Nama Produk</th>
                                        <th>Gudang JKT</th>
                                        <th>Gudang SBY</th>
                                        <th>Gudang BDG</th>
                                        <th>Total Fisik</th>
                                        <th>Tindakan Usulan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($productsStockSummary)): ?>
                                        <?php foreach ($productsStockSummary as $prod): ?>
                                            <?php
                                                $tStock = (int) $prod['total_stock'];
                                                $minStock = (int) $prod['min_stock_threshold'];
                                                $isLow = $tStock <= $minStock;
                                            ?>
                                            <tr>
                                                <td><code><?= $escape((string)$prod['sku']) ?></code></td>
                                                <td><strong><?= $escape((string)$prod['name']) ?></strong></td>
                                                <td><?= (int)$prod['stock_jkt'] ?> unit</td>
                                                <td><?= (int)$prod['stock_sby'] ?> unit</td>
                                                <td><?= (int)$prod['stock_bdg'] ?> unit</td>
                                                <td><strong class="<?= $isLow ? 'text-danger' : 'text-dark' ?>"><?= $tStock ?> unit</strong></td>
                                                <td>
                                                    <?php if ($isLow): ?>
                                                        <button class="btn-sm btn-secondary" onclick="document.querySelector('#nav-po').click(); SimulasiModule.createPurchaseOrder();">📋 Usulkan PO</button>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">Stok Cukup</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center text-muted">Belum ada alokasi stok produk.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div><!-- /master-view-warehouse -->
            </section>

            <!-- TAB 4: SALES ORDERS (SO) -->
            <section id="tab-sales-orders" class="tab-panel">
                <div class="section-title-bar">
                    <div>
                        <h2>Sales Orders (SO) — Penjualan</h2>
                        <p class="muted">Alur: Sales (Draft ➔ Pending) ➔ Admin (Approve/Reject) ➔ Staff Gudang (Goods Issue ➔ Fulfilled).</p>
                    </div>
                    <div style="display:flex;gap:0.5rem;align-items:center;">
                        <a href="/export/csv?type=so" download="daftar_sales_orders.csv" class="btn btn-secondary" id="btn-export-so">📥 Ekspor SO (CSV)</a>
                        <button type="button" class="btn btn-primary" id="btn-create-so">
                            ➕ Buat Sales Order Baru
                        </button>
                    </div>
                </div>

                <div class="panel-card">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No. SO</th>
                                    <th>Customer</th>
                                    <th>Gudang Asal</th>
                                    <th>Item &amp; Qty</th>
                                    <th>Sales Pembuat</th>
                                    <th>Status Order</th>
                                    <th>Tindakan Peran</th>
                                </tr>
                            </thead>
                            <tbody id="so-table-body">
                                <?php if (!empty($salesOrdersList)): ?>
                                    <?php foreach ($salesOrdersList as $so): ?>
                                        <?php
                                            $soId = (int) $so['id'];
                                            $status = (string) $so['status'];
                                            $badgeMap = [
                                                'draft' => 'badge-secondary',
                                                'pending_approval' => 'badge-warning',
                                                'approved' => 'badge-blue',
                                                'rejected' => 'badge-danger',
                                                'fulfilled' => 'badge-success',
                                                'cancelled' => 'badge-secondary'
                                            ];
                                            $bClass = $badgeMap[$status] ?? 'badge-secondary';
                                        ?>
                                        <tr id="so-row-<?= $soId ?>" data-so-id="<?= $soId ?>" data-so-status="<?= $status ?>" data-so-creator="<?= (int)($so['created_by'] ?? 0) ?>">
                                            <td><strong>#<?= $escape((string)$so['so_number']) ?></strong></td>
                                            <td><?= $escape((string)$so['customer_name']) ?></td>
                                            <td><?= $escape((string)$so['warehouse_name']) ?></td>
                                            <td><?= $escape((string)$so['items_summary']) ?></td>
                                            <td><?= $escape((string)$so['creator_name']) ?></td>
                                            <td><span class="badge <?= $bClass ?>" id="so-status-<?= $soId ?>"><?= $escape($status) ?></span></td>
                                            <td class="action-cell" id="so-action-<?= $soId ?>"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center text-muted">Belum ada data Sales Order.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- TAB 5: PURCHASE ORDERS & GOODS RECEIPT -->
            <section id="tab-purchase-orders" class="tab-panel">
                <div class="section-title-bar">
                    <div>
                        <h2>Purchase Orders (PO) &amp; Goods Receipt</h2>
                        <p class="muted">Pembelian barang dari Supplier untuk menambah stok gudang yang menipis.</p>
                    </div>
                    <div style="display:flex;gap:0.5rem;align-items:center;">
                        <a href="/export/csv?type=po" download="daftar_purchase_orders.csv" class="btn btn-secondary" id="btn-export-po">📥 Ekspor PO (CSV)</a>
                        <button type="button" class="btn btn-primary" id="btn-create-po" onclick="SimulasiModule.createPurchaseOrder()">
                            🚛 Buat Purchase Order (PO) Baru
                        </button>
                    </div>
                </div>

                <div class="panel-card">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No. PO</th>
                                    <th>Supplier</th>
                                    <th>Gudang Tujuan</th>
                                    <th>Item &amp; Qty</th>
                                    <th>Pembuat PO</th>
                                    <th>Status PO</th>
                                    <th>Tindakan Gudang</th>
                                </tr>
                            </thead>
                            <tbody id="po-table-body">
                                <?php if (!empty($purchaseOrdersList)): ?>
                                    <?php foreach ($purchaseOrdersList as $po): ?>
                                        <?php
                                            $poId = (int) $po['id'];
                                            $status = (string) $po['status'];
                                            $bClass = $status === 'goods_received' ? 'badge-success' : 'badge-blue';
                                        ?>
                                        <tr id="po-row-<?= $poId ?>" data-po-id="<?= $poId ?>" data-po-status="<?= $status ?>">
                                            <td><strong>#<?= $escape((string)$po['po_number']) ?></strong></td>
                                            <td><?= $escape((string)$po['supplier_name']) ?></td>
                                            <td><?= $escape((string)$po['warehouse_name']) ?></td>
                                            <td><?= $escape((string)$po['items_summary']) ?></td>
                                            <td><?= $escape((string)$po['creator_name']) ?></td>
                                            <td><span class="badge <?= $bClass ?>" id="po-status-<?= $poId ?>"><?= $escape($status) ?></span></td>
                                            <td id="po-action-<?= $poId ?>"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center text-muted">Belum ada data Purchase Order.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- TAB 6: STOCK LEDGER MULTIGUDANG -->
            <section id="tab-stock-ledger" class="tab-panel">
                <div class="section-title-bar">
                    <div>
                        <h2>Stock Ledger (Kartu Stok Audit Trail)</h2>
                        <p class="muted">Catatan permanen pergerakan stok (Masuk / Keluar / Transfer) di seluruh gudang.</p>
                    </div>
                    <a href="/export/csv?type=ledger" download="kartu_stok_ledger_audit.csv" class="btn btn-secondary" id="btn-export-ledger">📥 Ekspor Ledger (CSV)</a>
                </div>

                <div class="panel-card">
                    <div class="table-responsive">
                        <table class="data-table" id="table-stock-ledger">
                            <thead>
                                <tr>
                                    <th>Waktu &amp; Tanggal</th>
                                    <th>Gudang</th>
                                    <th>Produk (SKU)</th>
                                    <th>Tipe Pergerakan</th>
                                    <th>Perubahan Qty</th>
                                    <th>Stok Akhir</th>
                                    <th>Dokumen Referensi</th>
                                    <th>Petugas (User)</th>
                                </tr>
                            </thead>
                            <tbody id="stock-ledger-body">
                                <?php if (!empty($stockLedgerList)): ?>
                                    <?php foreach ($stockLedgerList as $sl): ?>
                                        <?php
                                            $delta = (int) $sl['quantity_delta'];
                                            $isPos = $delta > 0;
                                            $badgeClass = $isPos ? 'badge-success' : 'badge-danger';
                                            $deltaLabel = ($isPos ? '+' : '') . $delta . ' unit';
                                            $actionLabel = $isPos ? '+ STOK MASUK' : '- STOK KELUAR';
                                            $dateStr = substr((string)$sl['created_at'], 0, 16);
                                        ?>
                                        <tr>
                                            <td><?= $escape($dateStr) ?></td>
                                            <td><?= $escape((string)$sl['warehouse_name']) ?></td>
                                            <td><?= $escape((string)$sl['product_name'] . ' (' . $sl['product_sku'] . ')') ?></td>
                                            <td><span class="badge <?= $badgeClass ?>"><?= $escape($actionLabel) ?></span></td>
                                            <td><?= $escape($deltaLabel) ?></td>
                                            <td><strong><?= (int)$sl['balance_after'] ?> unit</strong></td>
                                            <td><code><?= $escape((string)$sl['reference_doc_number']) ?></code></td>
                                            <td><?= $escape((string)$sl['user_name']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center text-muted">Belum ada mutasi tercatat di Stock Ledger.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <!-- Modal Alur Operasional Sistem -->
    <div id="modal-flow-guide" class="modal-overlay" hidden>
        <div class="modal-card">
            <div class="modal-header">
                <h3>⚡ Alur Operasional Sistem 3 Peran</h3>
                <button type="button" class="modal-close-btn" id="btn-close-flow-modal" title="Tutup Modal">&times;</button>
            </div>
            <div class="modal-body">
                <ol class="flow-steps-list">
                    <li>
                        <div class="step-num">1</div>
                        <div class="step-content">
                            <strong>Admin: Kelola User &amp; Master Data</strong>
                            <p>Mengatur peran (Sales / Staff Gudang) dan master produk, gudang, supplier, &amp; customer.</p>
                        </div>
                    </li>
                    <li>
                        <div class="step-num">2</div>
                        <div class="step-content">
                            <strong>Sales: Buat &amp; Ajukan Sales Order</strong>
                            <p>Membuat draft order berdasarkan stok tersedia dan mengajukan ke status <code>PendingApproval</code>.</p>
                        </div>
                    </li>
                    <li>
                        <div class="step-num">3</div>
                        <div class="step-content">
                            <strong>Admin: Peninjauan &amp; Persetujuan</strong>
                            <p>Admin menyetujui (Approved) atau menolak order. Sales tidak dapat menyetujui order miliknya sendiri (Aturan SOD).</p>
                        </div>
                    </li>
                    <li>
                        <div class="step-num">4</div>
                        <div class="step-content">
                            <strong>Staff Gudang: Goods Issue &amp; Goods Receipt</strong>
                            <p>Memproses Goods Issue untuk mengurangi stok (SO), atau Goods Receipt untuk menambah stok (PO). Setiap transaksi tercatat otomatis pada Stock Ledger Multigudang.</p>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Modal Tambah User Baru (SOD Matrix) -->
    <div id="modal-add-user" class="modal-overlay" hidden>
        <div class="modal-card">
            <div class="modal-header">
                <h3>👥 Tambah Pengguna Baru (SOD Matrix)</h3>
                <button type="button" class="modal-close-btn" id="btn-close-add-user" title="Tutup Modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-add-user">
                    <div id="add-user-error" class="form-message" role="alert" hidden style="margin-bottom: 14px;"></div>

                    <div class="field" style="margin-bottom: 14px;">
                        <label for="new-user-name" style="display:block; font-weight:600; margin-bottom: 4px;">Nama Lengkap</label>
                        <input type="text" id="new-user-name" name="name" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="Contoh: Rina Rahmawati" required maxlength="100">
                    </div>

                    <div class="field" style="margin-bottom: 14px;">
                        <label for="new-user-email" style="display:block; font-weight:600; margin-bottom: 4px;">Alamat Email</label>
                        <input type="email" id="new-user-email" name="email" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="contoh: rina.sales@example.com" required maxlength="190">
                    </div>

                    <div class="field" style="margin-bottom: 14px;">
                        <label for="new-user-password" style="display:block; font-weight:600; margin-bottom: 4px;">Password Awal</label>
                        <input type="password" id="new-user-password" name="password" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="Minimal 8 karakter" required minlength="8" maxlength="72">
                    </div>

                    <div class="field" style="margin-bottom: 14px;">
                        <label for="new-user-role" style="display:block; font-weight:600; margin-bottom: 4px;">Peran (Hak Akses SOD)</label>
                        <select id="new-user-role" name="role" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: #fff;">
                            <option value="sales">Sales Staff (Katalog &amp; Buat SO)</option>
                            <option value="warehouse">Staff Gudang (Goods Issue/Receipt, Usul PO)</option>
                            <option value="admin">Admin System (Akses Penuh &amp; Approve)</option>
                        </select>
                    </div>

                    <div class="field" style="margin-bottom: 20px;">
                        <label for="new-user-warehouse" style="display:block; font-weight:600; margin-bottom: 4px;">Gudang Penugasan</label>
                        <select id="new-user-warehouse" name="warehouse" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: #fff;">
                            <option value="Semua Gudang (Pusat)">Semua Gudang (Pusat)</option>
                            <option value="Gudang Utama Jakarta">Gudang Utama Jakarta</option>
                            <option value="Gudang Cabang Surabaya">Gudang Cabang Surabaya</option>
                            <option value="Gudang Cabang Bandung">Gudang Cabang Bandung</option>
                        </select>
                    </div>

                    <div class="modal-actions-bar" style="justify-content: flex-end;">
                        <div class="modal-actions-right">
                            <button type="button" class="modal-btn modal-btn-secondary" id="btn-cancel-add-user">Batal</button>
                            <button type="submit" class="modal-btn modal-btn-primary" id="btn-submit-add-user">💾 Simpan Pengguna</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit & Hapus Pengguna (SOD Matrix) -->
    <div id="modal-edit-user" class="modal-overlay" hidden>
        <div class="modal-card">
            <div class="modal-header">
                <h3>✏️ Edit Pengguna &amp; Hak Akses (SOD)</h3>
                <button type="button" class="modal-close-btn" id="btn-close-edit-user" title="Tutup Modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-edit-user">
                    <div id="edit-user-error" class="form-message" role="alert" hidden style="margin-bottom: 12px;"></div>

                    <input type="hidden" id="edit-user-id" name="id">

                    <div class="field" style="margin-bottom: 11px;">
                        <label for="edit-user-code" style="display:block; font-weight:600; margin-bottom: 4px;">Kode Pengguna</label>
                        <input type="text" id="edit-user-code" class="input" style="width:100%; padding: 7px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: #f3f4f6;" readonly>
                    </div>

                    <div class="field" style="margin-bottom: 11px;">
                        <label for="edit-user-name" style="display:block; font-weight:600; margin-bottom: 4px;">Nama Lengkap</label>
                        <input type="text" id="edit-user-name" name="name" class="input" style="width:100%; padding: 7px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" required maxlength="100">
                    </div>

                    <div class="field" style="margin-bottom: 11px;">
                        <label for="edit-user-email" style="display:block; font-weight:600; margin-bottom: 4px;">Alamat Email</label>
                        <input type="email" id="edit-user-email" name="email" class="input" style="width:100%; padding: 7px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" required maxlength="190">
                    </div>

                    <div class="field" style="margin-bottom: 11px;">
                        <label for="edit-user-password" style="display:block; font-weight:600; margin-bottom: 4px;">Password Baru <small class="text-muted">(Kosongkan jika tidak ingin diubah)</small></label>
                        <input type="password" id="edit-user-password" name="password" class="input" style="width:100%; padding: 7px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="Minimal 8 karakter jika ingin diubah" minlength="8" maxlength="72">
                    </div>

                    <div class="field" style="margin-bottom: 11px;">
                        <label for="edit-user-role" style="display:block; font-weight:600; margin-bottom: 4px;">Peran (Hak Akses SOD)</label>
                        <select id="edit-user-role" name="role" class="input" style="width:100%; padding: 7px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: #fff;">
                            <option value="sales">Sales Staff (Katalog &amp; Buat SO)</option>
                            <option value="warehouse">Staff Gudang (Goods Issue/Receipt, Usul PO)</option>
                            <option value="admin">Admin System (Akses Penuh &amp; Approve)</option>
                        </select>
                    </div>

                    <div class="field" style="margin-bottom: 11px;">
                        <label for="edit-user-warehouse" style="display:block; font-weight:600; margin-bottom: 4px;">Gudang Penugasan</label>
                        <select id="edit-user-warehouse" name="warehouse" class="input" style="width:100%; padding: 7px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: #fff;">
                            <option value="Semua Gudang (Pusat)">Semua Gudang (Pusat)</option>
                            <option value="Gudang Utama Jakarta">Gudang Utama Jakarta</option>
                            <option value="Gudang Cabang Surabaya">Gudang Cabang Surabaya</option>
                            <option value="Gudang Cabang Bandung">Gudang Cabang Bandung</option>
                        </select>
                    </div>

                    <div class="field" style="margin-bottom: 14px;">
                        <label for="edit-user-status" style="display:block; font-weight:600; margin-bottom: 4px;">Status Akun</label>
                        <select id="edit-user-status" name="status" class="input" style="width:100%; padding: 7px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: #fff;">
                            <option value="active">Aktif (Dapat Login &amp; Transaksi)</option>
                            <option value="inactive">Nonaktif (Diblokir)</option>
                        </select>
                    </div>

                    <!-- Inline Konfirmasi Hapus Pengguna (Bebas Dialog Native / Anti-Block) -->
                    <div id="delete-user-confirm-box" hidden style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 10px 14px; margin-top: 14px; margin-bottom: 4px;">
                        <p style="color: #991b1b; font-size: 12.5px; font-weight: 600; margin: 0 0 8px 0;">
                            ⚠️ Yakin ingin menghapus pengguna ini secara permanen dari database?
                        </p>
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button type="button" id="btn-cancel-delete-confirm" class="modal-btn modal-btn-secondary" style="height: 30px; padding: 0 12px; font-size: 11.5px;">Batal</button>
                            <button type="button" id="btn-confirm-delete-action" class="modal-btn modal-btn-danger" style="height: 30px; padding: 0 12px; font-size: 11.5px;">Ya, Hapus Pengguna</button>
                        </div>
                    </div>

                    <div class="modal-actions-bar">
                        <button type="button" class="modal-btn modal-btn-danger" id="btn-delete-user">🗑️ Hapus Pengguna</button>
                        <div class="modal-actions-right">
                            <button type="button" class="modal-btn modal-btn-secondary" id="btn-cancel-edit-user">Batal</button>
                            <button type="submit" class="modal-btn modal-btn-primary" id="btn-submit-edit-user">💾 Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Master Produk Baru -->
    <div id="modal-add-product" class="modal-overlay" hidden>
        <div class="modal-card">
            <div class="modal-header">
                <h3>📦 Tambah Master Produk Baru</h3>
                <button type="button" class="modal-close-btn" id="btn-close-add-product" title="Tutup Modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-add-product">
                    <div id="add-product-error" class="form-message" role="alert" hidden style="margin-bottom: 14px;"></div>

                    <div style="display:grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 12px;">
                        <div class="field">
                            <label for="new-prod-sku" style="display:block; font-weight:600; margin-bottom: 4px;">Kode SKU</label>
                            <input type="text" id="new-prod-sku" name="sku" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="PRD-005" required maxlength="50">
                        </div>
                        <div class="field">
                            <label for="new-prod-name" style="display:block; font-weight:600; margin-bottom: 4px;">Nama Produk / Barang</label>
                            <input type="text" id="new-prod-name" name="name" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="Contoh: Headset Gaming Wireless" required maxlength="150">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div class="field">
                            <label for="new-prod-category" style="display:block; font-weight:600; margin-bottom: 4px;">Kategori Produk</label>
                            <select id="new-prod-category" name="category_id" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: #fff;" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categoriesList as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>"><?= $escape((string)$c['name']) ?> (<?= $escape((string)$c['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="new-prod-unit" style="display:block; font-weight:600; margin-bottom: 4px;">Satuan (Unit)</label>
                            <input type="text" id="new-prod-unit" name="unit" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" value="unit" placeholder="unit, pcs, box" required maxlength="20">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div class="field">
                            <label for="new-prod-purchase" style="display:block; font-weight:600; margin-bottom: 4px;">Harga Beli (Rp)</label>
                            <input type="number" id="new-prod-purchase" name="purchase_price" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" min="0" step="500" value="0" required>
                        </div>
                        <div class="field">
                            <label for="new-prod-selling" style="display:block; font-weight:600; margin-bottom: 4px;">Harga Jual (Rp)</label>
                            <input type="number" id="new-prod-selling" name="selling_price" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" min="0" step="500" value="0" required>
                        </div>
                        <div class="field">
                            <label for="new-prod-min" style="display:block; font-weight:600; margin-bottom: 4px;">Batas Minimum</label>
                            <input type="number" id="new-prod-min" name="min_stock_threshold" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" min="0" value="10" required>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-sm); padding: 12px; margin-bottom: 16px;">
                        <span style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom: 8px;">Alokasi Saldo Stok Awal Fisik (Multigudang):</span>
                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                            <div>
                                <label for="new-prod-stock-jkt" style="display:block; font-size:11.5px; color:#64748b; margin-bottom:2px;">Gudang JKT</label>
                                <input type="number" id="new-prod-stock-jkt" name="stock_jkt" class="input" style="width:100%; padding: 6px 10px; font-size:12px; border: 1px solid #cbd5e1; border-radius: 4px;" min="0" value="0">
                            </div>
                            <div>
                                <label for="new-prod-stock-sby" style="display:block; font-size:11.5px; color:#64748b; margin-bottom:2px;">Gudang SBY</label>
                                <input type="number" id="new-prod-stock-sby" name="stock_sby" class="input" style="width:100%; padding: 6px 10px; font-size:12px; border: 1px solid #cbd5e1; border-radius: 4px;" min="0" value="0">
                            </div>
                            <div>
                                <label for="new-prod-stock-bdg" style="display:block; font-size:11.5px; color:#64748b; margin-bottom:2px;">Gudang BDG</label>
                                <input type="number" id="new-prod-stock-bdg" name="stock_bdg" class="input" style="width:100%; padding: 6px 10px; font-size:12px; border: 1px solid #cbd5e1; border-radius: 4px;" min="0" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions-bar" style="justify-content: flex-end;">
                        <div class="modal-actions-right">
                            <button type="button" class="modal-btn modal-btn-secondary" id="btn-cancel-add-product">Batal</button>
                            <button type="submit" class="modal-btn modal-btn-primary" id="btn-submit-add-product">💾 Simpan Produk</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Kategori -->
    <div id="modal-add-category" class="modal-overlay" hidden>
        <div class="modal-card">
            <div class="modal-header">
                <h3>📁 Tambah Kategori Produk</h3>
                <button type="button" class="modal-close-btn" id="btn-close-add-category" title="Tutup Modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-add-category">
                    <div id="add-category-error" class="form-message" role="alert" hidden style="margin-bottom: 14px;"></div>

                    <div class="field" style="margin-bottom: 14px;">
                        <label for="new-cat-code" style="display:block; font-weight:600; margin-bottom: 4px;">Kode Kategori</label>
                        <input type="text" id="new-cat-code" name="code" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="Contoh: CAT-ACC" required maxlength="20">
                    </div>

                    <div class="field" style="margin-bottom: 14px;">
                        <label for="new-cat-name" style="display:block; font-weight:600; margin-bottom: 4px;">Nama Kategori</label>
                        <input type="text" id="new-cat-name" name="name" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="Contoh: Aksesoris Komputer" required maxlength="100">
                    </div>

                    <div class="field" style="margin-bottom: 18px;">
                        <label for="new-cat-desc" style="display:block; font-weight:600; margin-bottom: 4px;">Deskripsi (Opsional)</label>
                        <textarea id="new-cat-desc" name="description" class="input" style="width:100%; height:75px; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="Keterangan klasifikasi produk..."></textarea>
                    </div>

                    <div class="modal-actions-bar" style="justify-content: flex-end;">
                        <div class="modal-actions-right">
                            <button type="button" class="modal-btn modal-btn-secondary" id="btn-cancel-add-category">Batal</button>
                            <button type="submit" class="modal-btn modal-btn-primary" id="btn-submit-add-category">💾 Simpan Kategori</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Lokasi Gudang -->
    <div id="modal-add-warehouse" class="modal-overlay" hidden>
        <div class="modal-card">
            <div class="modal-header">
                <h3>🏪 Tambah Lokasi Gudang Baru</h3>
                <button type="button" class="modal-close-btn" id="btn-close-add-warehouse" title="Tutup Modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-add-warehouse">
                    <div id="add-warehouse-error" class="form-message" role="alert" hidden style="margin-bottom: 14px;"></div>

                    <div style="display:grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 14px;">
                        <div class="field">
                            <label for="new-wh-code" style="display:block; font-weight:600; margin-bottom: 4px;">Kode Gudang</label>
                            <input type="text" id="new-wh-code" name="code" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="WH-SMG" required maxlength="20">
                        </div>
                        <div class="field">
                            <label for="new-wh-name" style="display:block; font-weight:600; margin-bottom: 4px;">Nama Gudang</label>
                            <input type="text" id="new-wh-name" name="name" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="Gudang Cabang Semarang" required maxlength="100">
                        </div>
                    </div>

                    <div class="field" style="margin-bottom: 14px;">
                        <label for="new-wh-city" style="display:block; font-weight:600; margin-bottom: 4px;">Kota / Wilayah</label>
                        <input type="text" id="new-wh-city" name="city" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="Contoh: Semarang, Jawa Tengah" required maxlength="50">
                    </div>

                    <div class="field" style="margin-bottom: 18px;">
                        <label for="new-wh-address" style="display:block; font-weight:600; margin-bottom: 4px;">Alamat Lengkap</label>
                        <textarea id="new-wh-address" name="address" class="input" style="width:100%; height:75px; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="Alamat jalan, nomor gudang, kawasan industri..."></textarea>
                    </div>

                    <div class="modal-actions-bar" style="justify-content: flex-end;">
                        <div class="modal-actions-right">
                            <button type="button" class="modal-btn modal-btn-secondary" id="btn-cancel-add-warehouse">Batal</button>
                            <button type="submit" class="modal-btn modal-btn-primary" id="btn-submit-add-warehouse">💾 Simpan Gudang</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Supplier -->
    <div id="modal-add-supplier" class="modal-overlay" hidden>
        <div class="modal-card">
            <div class="modal-header">
                <h3>🏭 Tambah Pemasok / Supplier</h3>
                <button type="button" class="modal-close-btn" id="btn-close-add-supplier" title="Tutup Modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-add-supplier">
                    <div id="add-supplier-error" class="form-message" role="alert" hidden style="margin-bottom: 14px;"></div>

                    <div style="display:grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 14px;">
                        <div class="field">
                            <label for="new-sup-code" style="display:block; font-weight:600; margin-bottom: 4px;">Kode Supplier</label>
                            <input type="text" id="new-sup-code" name="code" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="SUP-003" required maxlength="20">
                        </div>
                        <div class="field">
                            <label for="new-sup-name" style="display:block; font-weight:600; margin-bottom: 4px;">Nama Perusahaan</label>
                            <input type="text" id="new-sup-name" name="name" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="PT Techindo Solusi" required maxlength="150">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div class="field">
                            <label for="new-sup-contact" style="display:block; font-weight:600; margin-bottom: 4px;">Contact Person</label>
                            <input type="text" id="new-sup-contact" name="contact_person" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="Bambang Santoso" maxlength="100">
                        </div>
                        <div class="field">
                            <label for="new-sup-phone" style="display:block; font-weight:600; margin-bottom: 4px;">Nomor Telepon</label>
                            <input type="text" id="new-sup-phone" name="phone" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="021-5551234" maxlength="30">
                        </div>
                    </div>

                    <div class="field" style="margin-bottom: 14px;">
                        <label for="new-sup-email" style="display:block; font-weight:600; margin-bottom: 4px;">Alamat Email</label>
                        <input type="email" id="new-sup-email" name="email" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="sales@techindo.co.id" maxlength="190">
                    </div>

                    <div class="field" style="margin-bottom: 18px;">
                        <label for="new-sup-address" style="display:block; font-weight:600; margin-bottom: 4px;">Alamat Operasional</label>
                        <textarea id="new-sup-address" name="address" class="input" style="width:100%; height:70px; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="Alamat kantor / gudang supplier..."></textarea>
                    </div>

                    <div class="modal-actions-bar" style="justify-content: flex-end;">
                        <div class="modal-actions-right">
                            <button type="button" class="modal-btn modal-btn-secondary" id="btn-cancel-add-supplier">Batal</button>
                            <button type="submit" class="modal-btn modal-btn-primary" id="btn-submit-add-supplier">💾 Simpan Supplier</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Customer -->
    <div id="modal-add-customer" class="modal-overlay" hidden>
        <div class="modal-card">
            <div class="modal-header">
                <h3>👤 Tambah Pelanggan / Customer (B2B)</h3>
                <button type="button" class="modal-close-btn" id="btn-close-add-customer" title="Tutup Modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-add-customer">
                    <div id="add-customer-error" class="form-message" role="alert" hidden style="margin-bottom: 14px;"></div>

                    <div style="display:grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 14px;">
                        <div class="field">
                            <label for="new-cust-code" style="display:block; font-weight:600; margin-bottom: 4px;">Kode Customer</label>
                            <input type="text" id="new-cust-code" name="code" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="CUST-004" required maxlength="20">
                        </div>
                        <div class="field">
                            <label for="new-cust-name" style="display:block; font-weight:600; margin-bottom: 4px;">Nama Customer / PT</label>
                            <input type="text" id="new-cust-name" name="name" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="CV Sukses Makmur" required maxlength="150">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div class="field">
                            <label for="new-cust-contact" style="display:block; font-weight:600; margin-bottom: 4px;">Contact Person</label>
                            <input type="text" id="new-cust-contact" name="contact_person" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="Dedi Suryana" maxlength="100">
                        </div>
                        <div class="field">
                            <label for="new-cust-phone" style="display:block; font-weight:600; margin-bottom: 4px;">Nomor Telepon</label>
                            <input type="text" id="new-cust-phone" name="phone" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="0812-9876543" maxlength="30">
                        </div>
                    </div>

                    <div class="field" style="margin-bottom: 14px;">
                        <label for="new-cust-email" style="display:block; font-weight:600; margin-bottom: 4px;">Alamat Email</label>
                        <input type="email" id="new-cust-email" name="email" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="procurement@suksesmakmur.com" maxlength="190">
                    </div>

                    <div class="field" style="margin-bottom: 18px;">
                        <label for="new-cust-address" style="display:block; font-weight:600; margin-bottom: 4px;">Alamat Pengiriman</label>
                        <textarea id="new-cust-address" name="address" class="input" style="width:100%; height:70px; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="Alamat gudang / kantor penerima barang..."></textarea>
                    </div>

                    <div class="modal-actions-bar" style="justify-content: flex-end;">
                        <div class="modal-actions-right">
                            <button type="button" class="modal-btn modal-btn-secondary" id="btn-cancel-add-customer">Batal</button>
                            <button type="submit" class="modal-btn modal-btn-primary" id="btn-submit-add-customer">💾 Simpan Customer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODALS: EDIT & HAPUS MASTER DATA (PRODUK, KATEGORI, GUDANG, SUPPLIER, CUSTOMER) -->
    <!-- ========================================================================= -->

    <!-- 1. Modal Edit & Hapus Master Produk -->
    <div id="modal-edit-product" class="modal-overlay" hidden>
        <div class="modal-card">
            <div class="modal-header">
                <h3>✏️ Edit Master Produk</h3>
                <button type="button" class="modal-close-btn" id="btn-close-edit-product" title="Tutup Modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-edit-product">
                    <div id="edit-product-error" class="form-message" role="alert" hidden style="margin-bottom: 14px;"></div>
                    <input type="hidden" id="edit-prod-id" name="id">

                    <div style="display:grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 12px;">
                        <div class="field">
                            <label for="edit-prod-sku" style="display:block; font-weight:600; margin-bottom: 4px;">Kode SKU</label>
                            <input type="text" id="edit-prod-sku" name="sku" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" required maxlength="50">
                        </div>
                        <div class="field">
                            <label for="edit-prod-name" style="display:block; font-weight:600; margin-bottom: 4px;">Nama Produk / Barang</label>
                            <input type="text" id="edit-prod-name" name="name" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" required maxlength="150">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div class="field">
                            <label for="edit-prod-category" style="display:block; font-weight:600; margin-bottom: 4px;">Kategori Produk</label>
                            <select id="edit-prod-category" name="category_id" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: #fff;" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categoriesList as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>"><?= $escape((string)$c['name']) ?> (<?= $escape((string)$c['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="edit-prod-unit" style="display:block; font-weight:600; margin-bottom: 4px;">Satuan (Unit)</label>
                            <input type="text" id="edit-prod-unit" name="unit" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" required maxlength="20">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div class="field">
                            <label for="edit-prod-purchase-price" style="display:block; font-weight:600; margin-bottom: 4px;">Harga Beli (Rp)</label>
                            <input type="number" id="edit-prod-purchase-price" name="purchase_price" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" min="0" step="1000" required>
                        </div>
                        <div class="field">
                            <label for="edit-prod-selling-price" style="display:block; font-weight:600; margin-bottom: 4px;">Harga Jual (Rp)</label>
                            <input type="number" id="edit-prod-selling-price" name="selling_price" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" min="0" step="1000" required>
                        </div>
                        <div class="field">
                            <label for="edit-prod-min-stock" style="display:block; font-weight:600; margin-bottom: 4px;">Batas Min. Stok</label>
                            <input type="number" id="edit-prod-min-stock" name="min_stock_threshold" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" min="1" required>
                        </div>
                    </div>

                    <div class="field" style="margin-bottom: 14px;">
                        <label for="edit-prod-status" style="display:block; font-weight:600; margin-bottom: 4px;">Status Produk</label>
                        <select id="edit-prod-status" name="is_active" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: #fff;">
                            <option value="1">Aktif (Dapat Ditransaksikan)</option>
                            <option value="0">Nonaktif (Diarsipkan)</option>
                        </select>
                    </div>

                    <!-- Inline Konfirmasi Hapus Produk -->
                    <div id="delete-product-confirm-box" hidden style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 10px 14px; margin-top: 14px; margin-bottom: 4px;">
                        <p style="color: #991b1b; font-size: 12.5px; font-weight: 600; margin: 0 0 8px 0;">
                            ⚠️ Yakin ingin menghapus produk ini dari database? Riwayat stok produk ini akan dibersihkan.
                        </p>
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button type="button" id="btn-cancel-delete-product-confirm" class="modal-btn modal-btn-secondary" style="height: 30px; padding: 0 12px; font-size: 11.5px;">Batal</button>
                            <button type="button" id="btn-confirm-delete-product-action" class="modal-btn modal-btn-danger" style="height: 30px; padding: 0 12px; font-size: 11.5px;">Ya, Hapus Produk</button>
                        </div>
                    </div>

                    <div class="modal-actions-bar">
                        <button type="button" class="modal-btn modal-btn-danger" id="btn-delete-product">🗑️ Hapus Produk</button>
                        <div class="modal-actions-right">
                            <button type="button" class="modal-btn modal-btn-secondary" id="btn-cancel-edit-product">Batal</button>
                            <button type="submit" class="modal-btn modal-btn-primary" id="btn-submit-edit-product">💾 Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. Modal Edit & Hapus Kategori -->
    <div id="modal-edit-category" class="modal-overlay" hidden>
        <div class="modal-card">
            <div class="modal-header">
                <h3>✏️ Edit Kategori Produk</h3>
                <button type="button" class="modal-close-btn" id="btn-close-edit-category" title="Tutup Modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-edit-category">
                    <div id="edit-category-error" class="form-message" role="alert" hidden style="margin-bottom: 14px;"></div>
                    <input type="hidden" id="edit-cat-id" name="id">

                    <div style="display:grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 14px;">
                        <div class="field">
                            <label for="edit-cat-code" style="display:block; font-weight:600; margin-bottom: 4px;">Kode Kategori</label>
                            <input type="text" id="edit-cat-code" name="code" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" required maxlength="20">
                        </div>
                        <div class="field">
                            <label for="edit-cat-name" style="display:block; font-weight:600; margin-bottom: 4px;">Nama Kategori</label>
                            <input type="text" id="edit-cat-name" name="name" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" required maxlength="100">
                        </div>
                    </div>

                    <div class="field" style="margin-bottom: 18px;">
                        <label for="edit-cat-desc" style="display:block; font-weight:600; margin-bottom: 4px;">Deskripsi / Keterangan</label>
                        <textarea id="edit-cat-desc" name="description" class="input" style="width:100%; height:75px; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);"></textarea>
                    </div>

                    <!-- Inline Konfirmasi Hapus Kategori -->
                    <div id="delete-category-confirm-box" hidden style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 10px 14px; margin-top: 14px; margin-bottom: 4px;">
                        <p style="color: #991b1b; font-size: 12.5px; font-weight: 600; margin: 0 0 8px 0;">
                            ⚠️ Yakin ingin menghapus kategori ini? Pastikan tidak ada produk yang menggunakan kategori ini.
                        </p>
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button type="button" id="btn-cancel-delete-category-confirm" class="modal-btn modal-btn-secondary" style="height: 30px; padding: 0 12px; font-size: 11.5px;">Batal</button>
                            <button type="button" id="btn-confirm-delete-category-action" class="modal-btn modal-btn-danger" style="height: 30px; padding: 0 12px; font-size: 11.5px;">Ya, Hapus Kategori</button>
                        </div>
                    </div>

                    <div class="modal-actions-bar">
                        <button type="button" class="modal-btn modal-btn-danger" id="btn-delete-category">🗑️ Hapus Kategori</button>
                        <div class="modal-actions-right">
                            <button type="button" class="modal-btn modal-btn-secondary" id="btn-cancel-edit-category">Batal</button>
                            <button type="submit" class="modal-btn modal-btn-primary" id="btn-submit-edit-category">💾 Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 3. Modal Edit & Hapus Lokasi Gudang -->
    <div id="modal-edit-warehouse" class="modal-overlay" hidden>
        <div class="modal-card">
            <div class="modal-header">
                <h3>✏️ Edit Lokasi Gudang</h3>
                <button type="button" class="modal-close-btn" id="btn-close-edit-warehouse" title="Tutup Modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-edit-warehouse">
                    <div id="edit-warehouse-error" class="form-message" role="alert" hidden style="margin-bottom: 14px;"></div>
                    <input type="hidden" id="edit-wh-id" name="id">

                    <div style="display:grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 14px;">
                        <div class="field">
                            <label for="edit-wh-code" style="display:block; font-weight:600; margin-bottom: 4px;">Kode Gudang</label>
                            <input type="text" id="edit-wh-code" name="code" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" required maxlength="20">
                        </div>
                        <div class="field">
                            <label for="edit-wh-name" style="display:block; font-weight:600; margin-bottom: 4px;">Nama Gudang</label>
                            <input type="text" id="edit-wh-name" name="name" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" required maxlength="100">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div class="field">
                            <label for="edit-wh-city" style="display:block; font-weight:600; margin-bottom: 4px;">Kota / Wilayah</label>
                            <input type="text" id="edit-wh-city" name="city" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" required maxlength="50">
                        </div>
                        <div class="field">
                            <label for="edit-wh-status" style="display:block; font-weight:600; margin-bottom: 4px;">Status Operasional</label>
                            <select id="edit-wh-status" name="is_active" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: #fff;">
                                <option value="1">Aktif Beroperasi</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="field" style="margin-bottom: 18px;">
                        <label for="edit-wh-address" style="display:block; font-weight:600; margin-bottom: 4px;">Alamat Lengkap</label>
                        <textarea id="edit-wh-address" name="address" class="input" style="width:100%; height:75px; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);"></textarea>
                    </div>

                    <!-- Inline Konfirmasi Hapus Gudang -->
                    <div id="delete-warehouse-confirm-box" hidden style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 10px 14px; margin-top: 14px; margin-bottom: 4px;">
                        <p style="color: #991b1b; font-size: 12.5px; font-weight: 600; margin: 0 0 8px 0;">
                            ⚠️ Yakin ingin menghapus gudang ini? Pastikan gudang tidak menyimpan stok fisik atau terikat transaksi.
                        </p>
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button type="button" id="btn-cancel-delete-warehouse-confirm" class="modal-btn modal-btn-secondary" style="height: 30px; padding: 0 12px; font-size: 11.5px;">Batal</button>
                            <button type="button" id="btn-confirm-delete-warehouse-action" class="modal-btn modal-btn-danger" style="height: 30px; padding: 0 12px; font-size: 11.5px;">Ya, Hapus Gudang</button>
                        </div>
                    </div>

                    <div class="modal-actions-bar">
                        <button type="button" class="modal-btn modal-btn-danger" id="btn-delete-warehouse">🗑️ Hapus Gudang</button>
                        <div class="modal-actions-right">
                            <button type="button" class="modal-btn modal-btn-secondary" id="btn-cancel-edit-warehouse">Batal</button>
                            <button type="submit" class="modal-btn modal-btn-primary" id="btn-submit-edit-warehouse">💾 Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 4. Modal Edit & Hapus Supplier -->
    <div id="modal-edit-supplier" class="modal-overlay" hidden>
        <div class="modal-card">
            <div class="modal-header">
                <h3>✏️ Edit Data Supplier</h3>
                <button type="button" class="modal-close-btn" id="btn-close-edit-supplier" title="Tutup Modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-edit-supplier">
                    <div id="edit-supplier-error" class="form-message" role="alert" hidden style="margin-bottom: 14px;"></div>
                    <input type="hidden" id="edit-sup-id" name="id">

                    <div style="display:grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 14px;">
                        <div class="field">
                            <label for="edit-sup-code" style="display:block; font-weight:600; margin-bottom: 4px;">Kode Supplier</label>
                            <input type="text" id="edit-sup-code" name="code" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" required maxlength="20">
                        </div>
                        <div class="field">
                            <label for="edit-sup-name" style="display:block; font-weight:600; margin-bottom: 4px;">Nama Perusahaan / Supplier</label>
                            <input type="text" id="edit-sup-name" name="name" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" required maxlength="150">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div class="field">
                            <label for="edit-sup-contact" style="display:block; font-weight:600; margin-bottom: 4px;">Contact Person</label>
                            <input type="text" id="edit-sup-contact" name="contact_person" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" maxlength="100">
                        </div>
                        <div class="field">
                            <label for="edit-sup-phone" style="display:block; font-weight:600; margin-bottom: 4px;">Nomor Telepon</label>
                            <input type="text" id="edit-sup-phone" name="phone" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" maxlength="30">
                        </div>
                    </div>

                    <div class="field" style="margin-bottom: 14px;">
                        <label for="edit-sup-email" style="display:block; font-weight:600; margin-bottom: 4px;">Alamat Email</label>
                        <input type="email" id="edit-sup-email" name="email" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" maxlength="190">
                    </div>

                    <div class="field" style="margin-bottom: 18px;">
                        <label for="edit-sup-address" style="display:block; font-weight:600; margin-bottom: 4px;">Alamat Operasional</label>
                        <textarea id="edit-sup-address" name="address" class="input" style="width:100%; height:75px; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);"></textarea>
                    </div>

                    <!-- Inline Konfirmasi Hapus Supplier -->
                    <div id="delete-supplier-confirm-box" hidden style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 10px 14px; margin-top: 14px; margin-bottom: 4px;">
                        <p style="color: #991b1b; font-size: 12.5px; font-weight: 600; margin: 0 0 8px 0;">
                            ⚠️ Yakin ingin menghapus supplier ini? Pastikan tidak ada transaksi Purchase Order (PO) terkait.
                        </p>
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button type="button" id="btn-cancel-delete-supplier-confirm" class="modal-btn modal-btn-secondary" style="height: 30px; padding: 0 12px; font-size: 11.5px;">Batal</button>
                            <button type="button" id="btn-confirm-delete-supplier-action" class="modal-btn modal-btn-danger" style="height: 30px; padding: 0 12px; font-size: 11.5px;">Ya, Hapus Supplier</button>
                        </div>
                    </div>

                    <div class="modal-actions-bar">
                        <button type="button" class="modal-btn modal-btn-danger" id="btn-delete-supplier">🗑️ Hapus Supplier</button>
                        <div class="modal-actions-right">
                            <button type="button" class="modal-btn modal-btn-secondary" id="btn-cancel-edit-supplier">Batal</button>
                            <button type="submit" class="modal-btn modal-btn-primary" id="btn-submit-edit-supplier">💾 Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 5. Modal Edit & Hapus Customer -->
    <div id="modal-edit-customer" class="modal-overlay" hidden>
        <div class="modal-card">
            <div class="modal-header">
                <h3>✏️ Edit Data Customer (B2B)</h3>
                <button type="button" class="modal-close-btn" id="btn-close-edit-customer" title="Tutup Modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-edit-customer">
                    <div id="edit-customer-error" class="form-message" role="alert" hidden style="margin-bottom: 14px;"></div>
                    <input type="hidden" id="edit-cust-id" name="id">

                    <div style="display:grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 14px;">
                        <div class="field">
                            <label for="edit-cust-code" style="display:block; font-weight:600; margin-bottom: 4px;">Kode Customer</label>
                            <input type="text" id="edit-cust-code" name="code" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" required maxlength="20">
                        </div>
                        <div class="field">
                            <label for="edit-cust-name" style="display:block; font-weight:600; margin-bottom: 4px;">Nama Customer / PT</label>
                            <input type="text" id="edit-cust-name" name="name" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" required maxlength="150">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div class="field">
                            <label for="edit-cust-contact" style="display:block; font-weight:600; margin-bottom: 4px;">Contact Person</label>
                            <input type="text" id="edit-cust-contact" name="contact_person" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" maxlength="100">
                        </div>
                        <div class="field">
                            <label for="edit-cust-phone" style="display:block; font-weight:600; margin-bottom: 4px;">Nomor Telepon</label>
                            <input type="text" id="edit-cust-phone" name="phone" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" maxlength="30">
                        </div>
                    </div>

                    <div class="field" style="margin-bottom: 14px;">
                        <label for="edit-cust-email" style="display:block; font-weight:600; margin-bottom: 4px;">Alamat Email</label>
                        <input type="email" id="edit-cust-email" name="email" class="input" style="width:100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" maxlength="190">
                    </div>

                    <div class="field" style="margin-bottom: 18px;">
                        <label for="edit-cust-address" style="display:block; font-weight:600; margin-bottom: 4px;">Alamat Pengiriman</label>
                        <textarea id="edit-cust-address" name="address" class="input" style="width:100%; height:75px; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);"></textarea>
                    </div>

                    <!-- Inline Konfirmasi Hapus Customer -->
                    <div id="delete-customer-confirm-box" hidden style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 10px 14px; margin-top: 14px; margin-bottom: 4px;">
                        <p style="color: #991b1b; font-size: 12.5px; font-weight: 600; margin: 0 0 8px 0;">
                            ⚠️ Yakin ingin menghapus customer ini? Pastikan tidak ada transaksi Sales Order (SO) terkait.
                        </p>
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button type="button" id="btn-cancel-delete-customer-confirm" class="modal-btn modal-btn-secondary" style="height: 30px; padding: 0 12px; font-size: 11.5px;">Batal</button>
                            <button type="button" id="btn-confirm-delete-customer-action" class="modal-btn modal-btn-danger" style="height: 30px; padding: 0 12px; font-size: 11.5px;">Ya, Hapus Customer</button>
                        </div>
                    </div>

                    <div class="modal-actions-bar">
                        <button type="button" class="modal-btn modal-btn-danger" id="btn-delete-customer">🗑️ Hapus Customer</button>
                        <div class="modal-actions-right">
                            <button type="button" class="modal-btn modal-btn-secondary" id="btn-cancel-edit-customer">Batal</button>
                            <button type="submit" class="modal-btn modal-btn-primary" id="btn-submit-edit-customer">💾 Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Buat Sales Order Baru (SO) -->
    <div id="modal-add-so" class="modal-overlay" hidden>
        <div class="modal-card modal-card-xl">
            <div class="modal-header">
                <h3>📝 Buat Sales Order (SO) Baru</h3>
                <button type="button" class="modal-close-btn" id="btn-close-add-so" title="Tutup Modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-add-so">
                    <div id="add-so-error" class="form-message" role="alert" hidden style="margin-bottom: 14px;"></div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div class="field">
                            <label for="new-so-customer" style="display:block; font-weight:600; margin-bottom: 6px;">Pilih Customer <span class="text-danger">*</span></label>
                            <select id="new-so-customer" name="customer_id" class="input" style="width:100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: #fff;" required>
                                <option value="">-- Pilih Customer --</option>
                                <?php foreach ($customersList as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>"><?= $escape((string)$c['name']) ?> (<?= $escape((string)$c['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="new-so-warehouse" style="display:block; font-weight:600; margin-bottom: 6px;">Pilih Gudang Pengiriman <span class="text-danger">*</span></label>
                            <select id="new-so-warehouse" name="warehouse_id" class="input" style="width:100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: #fff;" required>
                                <option value="">-- Pilih Gudang Pengiriman --</option>
                                <?php foreach ($warehousesList as $w): ?>
                                    <option value="<?= (int)$w['id'] ?>"><?= $escape((string)$w['name']) ?> (<?= $escape((string)$w['code']) ?> - <?= $escape((string)$w['city']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Tabel Item Produk Dinamis -->
                    <div class="field" style="margin-bottom: 16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                            <label style="font-weight:600; font-size: 14px; margin: 0;">
                                Daftar Item Barang / Produk <span class="text-danger">*</span>
                            </label>
                            <button type="button" class="btn-sm btn-secondary" id="btn-add-so-item-row" style="padding: 6px 12px; font-weight: 600;">➕ Tambah Baris Produk</button>
                        </div>
                        <div class="table-responsive" style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); max-height: 280px; overflow-y: auto;">
                            <table class="data-table" style="margin: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th style="min-width: 320px;">Produk (SKU / Nama / Stok Gudang)</th>
                                        <th style="width: 110px; text-align: right;">Kuantitas</th>
                                        <th style="width: 180px; text-align: right;">Harga Satuan (Rp)</th>
                                        <th style="width: 180px; text-align: right;">Subtotal (Rp)</th>
                                        <th style="width: 50px; text-align: center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="so-items-tbody">
                                    <!-- Dynamic Rows Injected by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Grand Total Banner -->
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <div>
                            <span class="text-muted" style="font-size: 13px;">Estimasi Total Nilai Sales Order:</span>
                            <div style="font-size: 24px; font-weight: 700; color: var(--color-primary);" id="so-grand-total-text">Rp 0</div>
                        </div>
                        <div class="text-muted" style="font-size: 12.5px; text-align: right; line-height: 1.5;">
                            🔒 <strong>Penegakan Aturan SOD:</strong><br>Sales membuat Draft &rarr; Diajukan ke Admin &rarr; Diproses Gudang
                        </div>
                    </div>

                    <div class="field" style="margin-bottom: 16px;">
                        <label for="new-so-notes" style="display:block; font-weight:600; margin-bottom: 6px;">Catatan Order (Opsional)</label>
                        <textarea id="new-so-notes" name="notes" class="input" style="width:100%; height:65px; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);" placeholder="Contoh: Pengiriman prioritas sebelum akhir bulan"></textarea>
                    </div>

                    <div class="modal-actions-bar">
                        <button type="button" class="modal-btn modal-btn-secondary" id="btn-cancel-add-so">Batal</button>
                        <div class="modal-actions-right" style="display: flex; gap: 10px;">
                            <button type="button" class="modal-btn modal-btn-secondary" id="btn-save-draft-so" style="border: 1px solid var(--border-color); font-weight:600;">💾 Simpan sebagai Draft</button>
                            <button type="submit" class="modal-btn modal-btn-primary" id="btn-submit-approval-so" style="font-weight:600;">🚀 Simpan &amp; Ajukan (Pending Approval)</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detail Sales Order (Rincian Item & Riwayat) -->
    <div id="modal-detail-so" class="modal-overlay" hidden>
        <div class="modal-card modal-card-xl">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <h3 id="detail-so-title">📦 Detail Sales Order</h3>
                    <span id="detail-so-badge" class="badge"></span>
                </div>
                <button type="button" class="modal-close-btn" id="btn-close-detail-so" title="Tutup Modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="detail-so-loading" style="text-align: center; padding: 24px;" class="text-muted">
                    Memuat data detail Sales Order...
                </div>
                <div id="detail-so-content" hidden>
                    <!-- Info Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; background: #f8fafc; padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); margin-bottom: 16px;">
                        <div>
                            <small class="text-muted" style="display: block;">No. Sales Order</small>
                            <strong id="detail-so-number" style="font-size: 15px;">-</strong>
                        </div>
                        <div>
                            <small class="text-muted" style="display: block;">Customer</small>
                            <strong id="detail-so-customer">-</strong>
                        </div>
                        <div>
                            <small class="text-muted" style="display: block;">Gudang Asal</small>
                            <strong id="detail-so-warehouse">-</strong>
                        </div>
                        <div>
                            <small class="text-muted" style="display: block;">Sales Pembuat</small>
                            <span id="detail-so-creator">-</span>
                        </div>
                        <div>
                            <small class="text-muted" style="display: block;">Approver (Admin)</small>
                            <span id="detail-so-approver">-</span>
                        </div>
                        <div>
                            <small class="text-muted" style="display: block;">Tanggal Pembuatan</small>
                            <span id="detail-so-date">-</span>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div id="detail-so-notes-container" style="margin-bottom: 16px; padding: 10px 14px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: var(--radius-sm);" hidden>
                        <small style="color: #92400e; font-weight: 600; display: block;">Catatan Khusus:</small>
                        <span id="detail-so-notes" style="color: #78350f; font-size: 13px;">-</span>
                    </div>

                    <!-- Items Table -->
                    <div style="margin-bottom: 16px;">
                        <h4 style="margin: 0 0 8px 0; font-size: 14px;">Rincian Item Produk</h4>
                        <div class="table-responsive" style="border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                            <table class="data-table" style="margin: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Nama Produk</th>
                                        <th style="text-align: right;">Kuantitas</th>
                                        <th style="text-align: right;">Harga Satuan</th>
                                        <th style="text-align: right;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="detail-so-items-body">
                                </tbody>
                                <tfoot>
                                    <tr style="font-weight: 700; background: #f8fafc;">
                                        <td colspan="4" style="text-align: right;">Total Nilai Pesanan:</td>
                                        <td id="detail-so-total" style="text-align: right; color: var(--color-primary); font-size: 15px;">Rp 0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Contextual Actions Bar Inside Detail Modal -->
                    <div class="modal-actions-bar" style="border-top: 1px solid var(--border-color); padding-top: 14px;">
                        <button type="button" class="modal-btn modal-btn-secondary" id="btn-close-detail-so-action">Tutup</button>
                        <div id="detail-so-contextual-actions" style="display: flex; gap: 8px;">
                            <!-- Injected dynamically based on role & status -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Katalog Produk untuk Helper JS Form SO -->
    <script id="products-catalog-data" type="application/json"><?= json_encode(array_map(static fn($p) => [
        'id' => (int)$p['id'],
        'sku' => (string)$p['sku'],
        'name' => (string)$p['name'],
        'price' => (float)$p['selling_price'],
        'stock' => (int)$p['total_stock'],
    ], $productsStockSummary), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?></script>

</body>
</html>
