<?php
declare(strict_types=1);

namespace App\Exception;

final class ForbiddenException extends AppException
{
    public function __construct(string $message = 'Akses tidak diizinkan.')
    {
        parent::__construct($message, 403);
    }
}
