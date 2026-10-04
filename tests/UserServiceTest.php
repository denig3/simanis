<?php
declare(strict_types=1);

namespace Tests;

use App\Exception\ConflictException;
use App\Exception\ValidationException;
use App\Model\User;
use App\Repository\UserRepositoryInterface;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    public function testCreateUserSuccess(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('emailExists')
            ->with('staff@example.com')
            ->willReturn(false);

        $repo->expects(self::once())
            ->method('create')
            ->with('Staff Name', 'staff@example.com', self::isType('string'), 'sales', 1, 'active')
            ->willReturn(42);

        $service = new UserService($repo);
        $result = $service->createUser([
            'name' => 'Staff Name',
            'email' => 'staff@example.com',
            'password' => 'SecurePass123!',
            'role' => 'sales',
            'warehouse' => 'Gudang Utama Jakarta',
        ]);

        self::assertSame(42, $result['id']);
        self::assertSame('#USR-42', $result['code']);
        self::assertSame('Staff Name', $result['name']);
        self::assertSame('staff@example.com', $result['email']);
        self::assertSame('sales', $result['role']);
        self::assertSame('active', $result['status']);
    }

    public function testCreateUserWithDuplicateEmailThrowsConflict(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('emailExists')
            ->with('existing@example.com')
            ->willReturn(true);

        $service = new UserService($repo);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('Email sudah terdaftar untuk pengguna lain.');

        $service->createUser([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => 'SecurePass123!',
            'role' => 'sales',
        ]);
    }

    public function testCreateUserWithInvalidInputThrowsValidation(): void
    {
        $repo = $this->createStub(UserRepositoryInterface::class);
        $service = new UserService($repo);

        $this->expectException(ValidationException::class);
        $service->createUser([
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'role' => 'invalid-role',
        ]);
    }

    public function testUpdateUserStatusToggleToInactive(): void
    {
        $existing = new User(10, 'Existing User', 'user@example.com', 'hash', 'sales', 1, 'active');

        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('findById')
            ->with(10)
            ->willReturn($existing);

        $repo->expects(self::once())
            ->method('update')
            ->with(10, 'Updated Name', 'user@example.com', 'warehouse', 2, 'inactive', null);

        $service = new UserService($repo);
        $result = $service->updateUser([
            'id' => 10,
            'name' => 'Updated Name',
            'email' => 'user@example.com',
            'role' => 'warehouse',
            'warehouse' => 'Gudang Cabang Surabaya',
            'status' => 'inactive',
        ]);

        self::assertSame('inactive', $result['status']);
        self::assertSame('Nonaktif', $result['status_label']);
    }

    public function testDeleteUserCannotDeleteSelf(): void
    {
        $repo = $this->createStub(UserRepositoryInterface::class);
        $service = new UserService($repo);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Anda tidak dapat menghapus akun Anda sendiri');

        $service->deleteUser(5, 5);
    }
}
