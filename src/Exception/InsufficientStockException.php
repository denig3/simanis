<?php
declare(strict_types=1);

namespace App\Exception;

final class InsufficientStockException extends AppException
{
    public function __construct(string $message = 'Stok produk tidak mencukupi untuk memenuhi pesanan.')
    {
        parent::__construct($message, 409);
    }
}
