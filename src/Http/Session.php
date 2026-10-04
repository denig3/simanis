<?php
declare(strict_types=1);

namespace App\Http;

use App\Domain\User;

final class Session
{
    public static function start(): void
    {
        session_name('inventory_session');
        session_set_cookie_params(['httponly' => true, 'secure' => getenv('SESSION_SECURE') === '1', 'samesite' => 'Lax', 'path' => '/']);
        session_start();
        $last = $_SESSION['last_activity'] ?? 0;
        if (is_int($last) && $last > 0 && time() - $last > 1800) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        $_SESSION['last_activity'] = time();
    }

    public static function token(): string
    {
        if (!isset($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function validToken(string $token): bool
    {
        return $token !== '' && hash_equals(self::token(), $token);
    }

    /** @return array{id: int, name: string, email: string}|null */
    public static function user(): ?array
    {
        /** @var array{id: int, name: string, email: string}|null $user */
        $user = $_SESSION['user'] ?? null;
        return $user;
    }

    public static function login(User $user): void
    {
        session_regenerate_id(true);
        $_SESSION = ['user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email], 'last_activity' => time()];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/', 'secure' => getenv('SESSION_SECURE') === '1', 'httponly' => true, 'samesite' => 'Lax']);
        session_destroy();
    }
}
