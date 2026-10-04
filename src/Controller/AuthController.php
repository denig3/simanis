<?php
declare(strict_types=1);

namespace App\Controller;

use App\Application\Authenticator;
use App\Http\Session;
use App\Infrastructure\LoginLimiter;

class AuthController extends BaseController
{
    public function __construct(private Authenticator $auth, private LoginLimiter $limiter) {}

    /** @param array<string, mixed> $input */
    public function login(array $input, string $ip): never
    {
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        if (!is_string($email) || !is_string($password) || strlen($email) > 190 || !filter_var(trim($email), FILTER_VALIDATE_EMAIL) || $password === '' || strlen($password) > 1024) {
            self::json(['message' => 'Masukkan email yang valid dan password Anda.'], 422);
        }
        $ipAllowed = $this->limiter->consume('ip:' . $ip, 30);
        $emailAllowed = $this->limiter->consume('email:' . strtolower(trim($email)), 5);
        if (!$ipAllowed || !$emailAllowed) {
            $retryAt = max(
                $ipAllowed ? 0 : $this->limiter->retryAt('ip:' . $ip),
                $emailAllowed ? 0 : $this->limiter->retryAt('email:' . strtolower(trim($email)))
            );
            self::json(['message' => 'Terlalu banyak percobaan. Silakan coba lagi jam ' . date('H:i', $retryAt) . '.'], 429);
        }
        $user = $this->auth->attempt($email, $password);
        if ($user === null) {
            self::json(['message' => 'Email atau password tidak sesuai.'], 401);
        }
        Session::login($user);
        self::json(['redirect' => '/dashboard']);
    }

    public function logout(): never
    {
        Session::logout();
        self::json(['redirect' => '/login']);
    }
}
