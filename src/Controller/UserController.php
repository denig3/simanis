<?php
declare(strict_types=1);

namespace App\Controller;

use App\Exception\UnauthorizedException;
use App\Service\UserService;
use Throwable;

final class UserController extends BaseController
{
    public function __construct(private UserService $userService) {}

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name: string, email: string}|null $sessionUser
     */
    public function create(array $input, ?array $sessionUser): never
    {
        if ($sessionUser === null) {
            self::json(['message' => 'Akses ditolak. Silakan login terlebih dahulu.'], 401);
        }

        try {
            $user = $this->userService->createUser($input);
            self::json([
                'message' => 'Pengguna baru berhasil ditambahkan.',
                'user' => $user,
            ], 201);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name: string, email: string}|null $sessionUser
     */
    public function update(array $input, ?array $sessionUser): never
    {
        if ($sessionUser === null) {
            self::json(['message' => 'Akses ditolak. Silakan login terlebih dahulu.'], 401);
        }

        try {
            $user = $this->userService->updateUser($input);
            self::json([
                'message' => 'Data pengguna "' . ($user['name'] ?? '') . '" berhasil diperbarui di database.',
                'user' => $user,
            ]);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array{id: int|string, name: string, email: string}|null $sessionUser
     */
    public function delete(array $input, ?array $sessionUser): never
    {
        if ($sessionUser === null) {
            self::json(['message' => 'Akses ditolak. Silakan login terlebih dahulu.'], 401);
        }

        try {
            $targetId = (int) ($input['id'] ?? 0);
            $sessionUserId = (int) $sessionUser['id'];
            $result = $this->userService->deleteUser($targetId, $sessionUserId);
            self::json($result);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }
}
