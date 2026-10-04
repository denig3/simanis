<?php
declare(strict_types=1);

namespace App\Infrastructure;

use PDO;

final class Database
{
    public static function connect(): PDO
    {
        return new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST') ?: 'db', getenv('DB_DATABASE') ?: 'inventory'),
            getenv('DB_USERNAME') ?: 'inventory',
            getenv('DB_PASSWORD') ?: '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+07:00'",
            ],
        );
    }
}
