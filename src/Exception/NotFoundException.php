<?php
declare(strict_types=1);

namespace App\Exception;

final class NotFoundException extends AppException
{
    public function __construct(string $message = 'Data tidak ditemukan.')
    {
        parent::__construct($message, 404);
    }
}
