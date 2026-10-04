<?php
/*
 * ARCHITECTURE OVERVIEW: 4-LAYER PATTERN (Controller -> Service -> Model -> Repository)
 *
 * 1. Controller Layer (App\Controller):
 *    Menangani HTTP request, validasi format request, session/auth check, dan mengembalikan HTTP Response (JSON/HTML).
 * 2. Service Layer (App\Service):
 *    Mengelola business logic, aturan validasi data, orkestrasi antar repository, dan database transaction.
 * 3. Model Layer (App\Model):
 *    Representasi entitas data domain (User, Product, Category, Warehouse, Supplier, Customer, Order, StockLedger).
 * 4. Repository Layer (App\Repository):
 *    Mengelola akses data ke database MySQL menggunakan PDO Prepared Statements dengan BIND PARAMETER untuk keamanan maksimal.
 */

declare(strict_types=1);

use App\Application\Authenticator;
use App\Application\RegisterUser;
use App\Controller\AuthController;
use App\Controller\BaseController;
use App\Controller\CategoryController;
use App\Controller\CustomerController;
use App\Controller\DashboardController;
use App\Controller\ExportController;
use App\Controller\OrderController;
use App\Controller\ProductController;
use App\Controller\RegisterController;
use App\Controller\SupplierController;
use App\Controller\UserController;
use App\Controller\WarehouseController;
use App\Http\Session;
use App\Infrastructure\Database;
use App\Infrastructure\LoginLimiter;
use App\Infrastructure\PdoUserRepository as LegacyPdoUserRepository;
use App\Repository\PdoCategoryRepository;
use App\Repository\PdoCustomerRepository;
use App\Repository\PdoOrderRepository;
use App\Repository\PdoProductRepository;
use App\Repository\PdoSupplierRepository;
use App\Repository\PdoUserRepository;
use App\Repository\PdoWarehouseRepository;
use App\Service\CategoryService;
use App\Service\CustomerService;
use App\Service\DashboardService;
use App\Service\ExportService;
use App\Service\OrderService;
use App\Service\ProductService;
use App\Service\SupplierService;
use App\Service\UserService;
use App\Service\WarehouseService;

require_once dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('Asia/Jakarta');

// Security Headers
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self' data:; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");

Session::start();

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    // -------------------------------------------------------------------------
    // API CONTRACT: GET /api/products/{sku}/availability (API-01)
    // -------------------------------------------------------------------------
    if (is_string($path) && preg_match('#^/api/products/([^/]+)/availability$#', $path, $matches)) {
        if ($method !== 'GET') {
            header('Allow: GET');
            BaseController::json(['message' => 'Metode tidak diizinkan.'], 405);
        }

        $sessionUser = Session::user();
        if ($sessionUser === null) {
            BaseController::json(['message' => 'Autentikasi diperlukan untuk mengakses API inventaris.'], 401);
        }

        $pdo = Database::connect();
        $orderRepo = new PdoOrderRepository($pdo);
        $orderService = new OrderService($orderRepo);
        $orderController = new OrderController($orderService);

        $sku = urldecode($matches[1]);
        $orderController->getAvailability($sku, $sessionUser);
    }

    // -------------------------------------------------------------------------
    // API ROUTING (POST only, JSON response)
    // -------------------------------------------------------------------------
    $apiRoutes = [
        '/api/login',
        '/api/logout',
        '/api/register',
        '/api/users',
        '/api/users/update',
        '/api/users/delete',
        '/api/products/create',
        '/api/products/update',
        '/api/products/delete',
        '/api/categories/create',
        '/api/categories/update',
        '/api/categories/delete',
        '/api/warehouses/create',
        '/api/warehouses/update',
        '/api/warehouses/delete',
        '/api/suppliers/create',
        '/api/suppliers/update',
        '/api/suppliers/delete',
        '/api/customers/create',
        '/api/customers/update',
        '/api/customers/delete',
        '/api/orders/sales/detail',
        '/api/orders/sales/create',
        '/api/orders/sales/submit',
        '/api/orders/sales/approve',
        '/api/orders/sales/reject',
        '/api/orders/sales/fulfill',
        '/api/orders/sales/cancel',
        '/api/orders/purchase/detail',
        '/api/orders/purchase/create',
        '/api/orders/purchase/order',
        '/api/orders/purchase/receive',
        '/api/orders/purchase/cancel',
    ];

    if (in_array($path, $apiRoutes, true)) {
        if ($method !== 'POST') {
            header('Allow: POST');
            BaseController::json(['message' => 'Metode tidak diizinkan.'], 405);
        }

        // Fast path for logout (before CSRF or body checks)
        if ($path === '/api/logout') {
            Session::logout();
            BaseController::json(['redirect' => '/login']);
        }

        // CSRF Verification
        if (!Session::validToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
            BaseController::json(['message' => 'Sesi formulir berakhir. Muat ulang halaman.'], 403);
        }

        // Request body size limit (8 KiB)
        if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 8192) {
            BaseController::json(['message' => 'Permintaan terlalu besar.'], 413);
        }

        try {
            $input = json_decode(file_get_contents('php://input') ?: '', true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            BaseController::json(['message' => 'Format permintaan tidak valid.'], 400);
        }

        if (!is_array($input) || array_is_list($input)) {
            BaseController::json(['message' => 'Format permintaan tidak valid.'], 400);
        }

        /** @var array<string, mixed> $input */
        $pdo = Database::connect();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $sessionUser = Session::user();

        // 4-Layer Dependencies Initialization
        // Layer 4: Repositories (PDO + Bind Parameter)
        $userRepo = new PdoUserRepository($pdo);
        $productRepo = new PdoProductRepository($pdo);
        $categoryRepo = new PdoCategoryRepository($pdo);
        $warehouseRepo = new PdoWarehouseRepository($pdo);
        $supplierRepo = new PdoSupplierRepository($pdo);
        $customerRepo = new PdoCustomerRepository($pdo);
        $orderRepo = new PdoOrderRepository($pdo);

        // Layer 2: Services (Business Logic & Transactions)
        $userService = new UserService($userRepo);
        $productService = new ProductService($productRepo, $categoryRepo, $pdo);
        $categoryService = new CategoryService($categoryRepo);
        $warehouseService = new WarehouseService($warehouseRepo, $pdo);
        $supplierService = new SupplierService($supplierRepo);
        $customerService = new CustomerService($customerRepo);
        $orderService = new OrderService($orderRepo);

        // Layer 1: Controllers (HTTP Layer)
        $authController = new AuthController(new Authenticator(new LegacyPdoUserRepository($pdo)), new LoginLimiter($pdo));
        $registerController = new RegisterController(new RegisterUser(new LegacyPdoUserRepository($pdo)), new LoginLimiter($pdo));
        $userController = new UserController($userService);
        $productController = new ProductController($productService);
        $categoryController = new CategoryController($categoryService);
        $warehouseController = new WarehouseController($warehouseService);
        $supplierController = new SupplierController($supplierService);
        $customerController = new CustomerController($customerService);
        $orderController = new OrderController($orderService);

        // Controller Dispatching
        match ($path) {
            '/api/login' => $authController->login($input, $ip),
            '/api/register' => $registerController->register($input, $ip),
            '/api/users' => $userController->create($input, $sessionUser),
            '/api/users/update' => $userController->update($input, $sessionUser),
            '/api/users/delete' => $userController->delete($input, $sessionUser),
            '/api/products/create' => $productController->create($input, $sessionUser),
            '/api/products/update' => $productController->update($input, $sessionUser),
            '/api/products/delete' => $productController->delete($input, $sessionUser),
            '/api/categories/create' => $categoryController->create($input, $sessionUser),
            '/api/categories/update' => $categoryController->update($input, $sessionUser),
            '/api/categories/delete' => $categoryController->delete($input, $sessionUser),
            '/api/warehouses/create' => $warehouseController->create($input, $sessionUser),
            '/api/warehouses/update' => $warehouseController->update($input, $sessionUser),
            '/api/warehouses/delete' => $warehouseController->delete($input, $sessionUser),
            '/api/suppliers/create' => $supplierController->create($input, $sessionUser),
            '/api/suppliers/update' => $supplierController->update($input, $sessionUser),
            '/api/suppliers/delete' => $supplierController->delete($input, $sessionUser),
            '/api/customers/create' => $customerController->create($input, $sessionUser),
            '/api/customers/update' => $customerController->update($input, $sessionUser),
            '/api/customers/delete' => $customerController->delete($input, $sessionUser),
            '/api/orders/sales/detail' => $orderController->getSalesOrderDetail($input, $sessionUser),
            '/api/orders/sales/create' => $orderController->createSalesOrder($input, $sessionUser),
            '/api/orders/sales/submit' => $orderController->submitSalesOrder($input, $sessionUser),
            '/api/orders/sales/approve' => $orderController->approveSalesOrder($input, $sessionUser),
            '/api/orders/sales/reject' => $orderController->rejectSalesOrder($input, $sessionUser),
            '/api/orders/sales/fulfill' => $orderController->fulfillSalesOrder($input, $sessionUser),
            '/api/orders/sales/cancel' => $orderController->cancelSalesOrder($input, $sessionUser),
            '/api/orders/purchase/detail' => $orderController->getPurchaseOrderDetail($input, $sessionUser),
            '/api/orders/purchase/create' => $orderController->createPurchaseOrder($input, $sessionUser),
            '/api/orders/purchase/order' => $orderController->orderPurchaseOrder($input, $sessionUser),
            '/api/orders/purchase/receive' => $orderController->receivePurchaseOrder($input, $sessionUser),
            '/api/orders/purchase/cancel' => $orderController->cancelPurchaseOrder($input, $sessionUser),
        };
    }

    // -------------------------------------------------------------------------
    // PAGE ROUTING (GET only, HTML / CSV response)
    // -------------------------------------------------------------------------
    if ($method !== 'GET') {
        http_response_code(405);
        header('Allow: GET');
        exit('Metode tidak diizinkan.');
    }

    $user = Session::user();
    if ($path === '/' || ($path === '/dashboard' && $user === null) || (in_array($path, ['/login', '/register'], true) && $user !== null)) {
        header('Location: ' . ($user === null ? '/login' : '/dashboard'));
        exit;
    }

    $pdo = Database::connect();
    $userRepo = new PdoUserRepository($pdo);
    $productRepo = new PdoProductRepository($pdo);
    $categoryRepo = new PdoCategoryRepository($pdo);
    $warehouseRepo = new PdoWarehouseRepository($pdo);
    $supplierRepo = new PdoSupplierRepository($pdo);
    $customerRepo = new PdoCustomerRepository($pdo);
    $orderRepo = new PdoOrderRepository($pdo);

    if ($path === '/export/csv') {
        $exportService = new ExportService($productRepo, $categoryRepo, $warehouseRepo, $supplierRepo, $customerRepo, $orderRepo);
        $exportController = new ExportController($exportService);
        $exportController->csv($user, (string) ($_GET['type'] ?? 'products'), (string) ($_GET['delim'] ?? ','));
    }

    $csrf = Session::token();
    if ($path === '/login') {
        $registered = ($_SESSION['registration_success'] ?? false) === true;
        unset($_SESSION['registration_success']);
        require_once dirname(__DIR__) . '/templates/login.php';
    } elseif ($path === '/register') {
        require_once dirname(__DIR__) . '/templates/register.php';
    } elseif ($path === '/dashboard') {
        $dashboardService = new DashboardService($userRepo, $warehouseRepo, $categoryRepo, $supplierRepo, $customerRepo, $productRepo, $orderRepo);
        $dashboardController = new DashboardController($dashboardService, $pdo);
        $dashboardController->index($user, $csrf);
    } else {
        http_response_code(404);
        echo 'Halaman tidak ditemukan.';
    }
} catch (Throwable $exception) {
    error_log((string) $exception);
    BaseController::json(['message' => 'Layanan sementara tidak tersedia. Silakan coba kembali.'], 503);
}
