<?php
declare(strict_types=1);

namespace App\Application;

use App\Domain\UserRepository;
use InvalidArgumentException;

/**
 * Use case: Pendaftaran pengguna baru.
 * Refactored using Extract Method to enforce Single Responsibility and clean validations.
 */
final class RegisterUser
{
    public function __construct(private UserRepository $users) {}

    public function register(string $name, string $email, string $password, string $confirmation): void
    {
        $name = trim($name);
        $email = strtolower(trim($email));

        $this->validateName($name);
        $this->validateEmail($email);
        $this->validatePassword($password, $confirmation);

        $this->users->create($name, $email, password_hash($password, PASSWORD_DEFAULT));
    }

    private function validateName(string $name): void
    {
        if ($name === '' || strlen($name) > 100) {
            throw new InvalidArgumentException('Nama wajib diisi dan maksimal 100 byte.');
        }
    }

    private function validateEmail(string $email): void
    {
        if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Masukkan email yang valid.');
        }
    }

    private function validatePassword(string $password, string $confirmation): void
    {
        if (mb_strlen($password, 'UTF-8') < 12 || strlen($password) > 72) {
            throw new InvalidArgumentException('Password minimal 12 karakter dan maksimal 72 byte.');
        }
        if ($password !== $confirmation) {
            throw new InvalidArgumentException('Konfirmasi password tidak sama.');
        }
    }
}
