<?php
declare(strict_types=1);

namespace App\Infrastructure;

use PDO;
use Throwable;

final class DatabaseSeeder
{
    public static function seedIfEmpty(PDO $pdo): void
    {
        try {
            $checkTable = $pdo->query("SHOW TABLES LIKE 'products'");
            if ($checkTable && $checkTable->rowCount() > 0) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM products");
                $count = $stmt ? (int) $stmt->fetchColumn() : 0;
                if ($count >= 30) {
                    return; // Sudah terisi lengkap
                }
            }

            $sqlFile = dirname(__DIR__, 2) . '/database/schema.sql';
            if (!file_exists($sqlFile)) {
                return;
            }

            $sql = file_get_contents($sqlFile);
            if ($sql === false || trim($sql) === '') {
                return;
            }

            // Eksekusi skema dan seed
            $pdo->exec($sql);
        } catch (Throwable $e) {
            error_log('DatabaseSeeder error: ' . $e->getMessage());
        }
    }
}
