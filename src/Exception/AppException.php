<?php
declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

class AppException extends RuntimeException
{
    public function __construct(string $message, private int $statusCode = 400)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
