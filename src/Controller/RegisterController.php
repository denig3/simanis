<?php
declare(strict_types=1);

namespace App\Controller;

use App\Application\RegisterUser;
use App\Domain\EmailAlreadyRegistered;
use App\Infrastructure\LoginLimiter;
use InvalidArgumentException;

class RegisterController extends BaseController
{
    public function __construct(private RegisterUser $registration, private LoginLimiter $limiter) {}

    /** @param array<string, mixed> $input */
    public function register(array $input, string $ip): never
    {
        if (!$this->limiter->consume('register:' . $ip, 10)) {
            self::json(['message' => 'Terlalu banyak percobaan daftar. Coba lagi dalam 15 menit.'], 429);
        }
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $confirmation = $input['password_confirmation'] ?? '';
        if (!is_string($name) || !is_string($email) || !is_string($password) || !is_string($confirmation)) {
            self::json(['message' => 'Isian formulir tidak valid.'], 422);
        }
        try {
            $this->registration->register($name, $email, $password, $confirmation);
        } catch (InvalidArgumentException $exception) {
            self::json(['message' => $exception->getMessage()], 422);
        } catch (EmailAlreadyRegistered $exception) {
            self::json(['message' => $exception->getMessage()], 409);
        }
        $_SESSION['registration_success'] = true;
        self::json(['redirect' => '/login'], 201);
    }
}
