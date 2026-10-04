<?php
declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\User;
use App\Domain\UserRepository;
use PDO;

final class PdoUserRepository implements UserRepository
{
    public function __construct(private PDO $pdo) {}

    public function create(string $name, string $email, string $passwordHash): void
    {
        try {
            $query = $this->pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
            $query->execute([$name, $email, $passwordHash]);
        } catch (\PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                throw new \App\Domain\EmailAlreadyRegistered('Email sudah terdaftar. Silakan login.');
            }
            throw $exception;
        }
    }

    public function findByEmail(string $email): ?User
    {
        $query = $this->pdo->prepare('SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1');
        $query->execute(['email' => $email]);
        /** @var array{id: int|string, name: string, email: string, password_hash: string}|false $row */
        $row = $query->fetch();
        return $row === false ? null : new User((int) $row['id'], $row['name'], $row['email'], $row['password_hash']);
    }
}
