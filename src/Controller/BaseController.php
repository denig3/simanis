<?php
declare(strict_types=1);

namespace App\Controller;

use App\Exception\AppException;
use Throwable;

abstract class BaseController
{
    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_THROW_ON_ERROR);
        exit;
    }

    public static function handleException(Throwable $e): never
    {
        if ($e instanceof AppException) {
            self::json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        error_log((string) $e);
        self::json(['message' => 'Terjadi kesalahan sistem internal: ' . $e->getMessage()], 500);
    }
}
