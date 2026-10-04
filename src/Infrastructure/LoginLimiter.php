<?php
declare(strict_types=1);

namespace App\Infrastructure;

use PDO;

final class LoginLimiter
{
    public function __construct(private PDO $pdo) {}

    public function consume(string $key, int $limit): bool
    {
        $now = time();
        $bucket = hash('sha256', $key);
        $query = $this->pdo->prepare('INSERT INTO login_attempts (bucket, attempts, window_start) VALUES (:bucket, 1, :now) ON DUPLICATE KEY UPDATE attempts = IF(window_start < :cutoff, 1, attempts + 1), window_start = IF(window_start < :cutoff2, :now2, window_start)');
        $query->execute(['bucket' => $bucket, 'now' => $now, 'cutoff' => $now - 900, 'cutoff2' => $now - 900, 'now2' => $now]);
        $read = $this->pdo->prepare('SELECT attempts FROM login_attempts WHERE bucket = ?');
        $read->execute([$bucket]);
        $allowed = (int) $read->fetchColumn() <= $limit;
        $del = $this->pdo->prepare('DELETE FROM login_attempts WHERE window_start < ?');
        $del->execute([$now - 86400]);
        return $allowed;
    }
}
