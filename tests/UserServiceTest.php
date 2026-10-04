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
}
