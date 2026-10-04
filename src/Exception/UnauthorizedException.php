<?php
declare(strict_types=1);

namespace App\Exception;

final class UnauthorizedException extends AppException
{
    public function __construct(string $message = 'Akses ditolak. Silakan login terlebih dahulu.')
    {
        parent::__construct($message, 401);
    }
}
