<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\UserRepositoryInterface;

final class UserService
{
    /** @var array<string, int> */
    private const WAREHOUSE_MAP = [
        'Gudang Utama Jakarta' => 1,
        'Gudang Cabang Surabaya' => 2,
        'Gudang Cabang Bandung' => 3,
    ];

    public function __construct(private UserRepositoryInterface $userRepository) {}

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function createUser(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $email = trim(strtolower((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');
        $role = (string) ($input['role'] ?? 'sales');
        $warehouse = trim((string) ($input['warehouse'] ?? ''));

        if ($name === '' || strlen($name) > 100) {
            throw new ValidationException('Nama lengkap wajib diisi (maks 100 karakter).');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Format email tidak valid.');
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            throw new ValidationException('Password minimal 8 dan maksimal 72 karakter.');
        }
        if (!in_array($role, ['admin', 'sales', 'warehouse'], true)) {
            throw new ValidationException('Peran pengguna tidak valid.');
        }

        if ($this->userRepository->emailExists($email)) {
            throw new ConflictException('Email sudah terdaftar untuk pengguna lain.');
        }

        $assignedWhId = self::WAREHOUSE_MAP[$warehouse] ?? null;
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $newId = $this->userRepository->create($name, $email, $hash, $role, $assignedWhId, 'active');

        return [
            'id' => $newId,
            'code' => '#USR-' . str_pad((string) $newId, 2, '0', STR_PAD_LEFT),
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'warehouse' => $warehouse ?: 'Semua Gudang (Pusat)',
            'status' => 'active',
            'status_label' => 'Aktif',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function updateUser(array $input): array
    {
        $targetId = (int) ($input['id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        $email = trim(strtolower((string) ($input['email'] ?? '')));
        $role = (string) ($input['role'] ?? 'sales');
        $status = (string) ($input['status'] ?? 'active');
        $warehouse = trim((string) ($input['warehouse'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if ($targetId <= 0) {
            throw new ValidationException('ID pengguna tidak valid.');
        }
        if ($name === '' || strlen($name) > 100) {
            throw new ValidationException('Nama lengkap wajib diisi (maks 100 karakter).');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Format email tidak valid.');
        }
        if (!in_array($role, ['admin', 'sales', 'warehouse'], true)) {
            throw new ValidationException('Peran pengguna tidak valid.');
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new ValidationException('Status pengguna tidak valid.');
        }

        $currentUser = $this->userRepository->findById($targetId);
        if ($currentUser === null) {
            throw new NotFoundException('Pengguna tidak ditemukan di database.');
        }

        if ($email !== $currentUser->email && $this->userRepository->emailExists($email, $targetId)) {
            throw new ConflictException('Email sudah digunakan oleh pengguna lain.');
        }

        $assignedWhId = self::WAREHOUSE_MAP[$warehouse] ?? null;
        $hash = null;
        if ($password !== '') {
            if (strlen($password) < 8 || strlen($password) > 72) {
                throw new ValidationException('Password baru minimal 8 dan maksimal 72 karakter.');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->userRepository->update($targetId, $name, $email, $role, $assignedWhId, $status, $hash);

        return [
            'id' => $targetId,
            'code' => '#USR-' . str_pad((string) $targetId, 2, '0', STR_PAD_LEFT),
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'warehouse' => $warehouse ?: 'Semua Gudang (Pusat)',
            'status' => $status,
            'status_label' => $status === 'active' ? 'Aktif' : 'Nonaktif',
        ];
    }

    /**
     * @return array{message: string, action: string, user_id: int, user_name: string}
     */
    public function deleteUser(int $targetId, int $sessionUserId): array
    {
        if ($targetId <= 0) {
            throw new ValidationException('ID pengguna tidak valid.');
        }
        if ($targetId === $sessionUserId) {
            throw new ValidationException('Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        }

        $targetUser = $this->userRepository->findById($targetId);
        if ($targetUser === null) {
            throw new NotFoundException('Pengguna tidak ditemukan di database.');
        }

        $userName = $targetUser->name;
        $fallbackSales = $this->userRepository->findFallbackUserId('sales', $targetId) ?? $sessionUserId;
        $fallbackWarehouse = $this->userRepository->findFallbackUserId('warehouse', $targetId) ?? $sessionUserId;

        $this->userRepository->reassignUserRelations($targetId, $fallbackSales, $fallbackWarehouse, $sessionUserId);
        $this->userRepository->delete($targetId);

        return [
            'message' => 'Pengguna "' . $userName . '" berhasil dihapus dari database.',
            'action' => 'deleted',
            'user_id' => $targetId,
            'user_name' => $userName,
        ];
    }
}
