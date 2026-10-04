<?php
declare(strict_types=1);

namespace Tests;

use App\Application\Authenticator;
use App\Domain\User;
use App\Domain\UserRepository;
use PHPUnit\Framework\TestCase;

final class AuthenticatorTest extends TestCase
{
    public function testValidCredentialsReturnUserAndNormalizeEmail(): void
    {
        $user = new User(1, 'Admin', 'admin@example.com', password_hash('correct-password', PASSWORD_DEFAULT));
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::once())->method('findByEmail')->with('admin@example.com')->willReturn($user);
        self::assertSame($user, (new Authenticator($repository))->attempt(' ADMIN@example.com ', 'correct-password'));
    }

    public function testWrongPasswordIsRejected(): void
    {
        $repository = $this->createStub(UserRepository::class);
        $repository->method('findByEmail')->willReturn(new User(1, 'Admin', 'admin@example.com', password_hash('correct-password', PASSWORD_DEFAULT)));
        self::assertNull((new Authenticator($repository))->attempt('admin@example.com', 'wrong-password'));
    }

    public function testUnknownEmailIsRejectedEvenIfPasswordMatchesDummyHash(): void
    {
        $repository = $this->createStub(UserRepository::class);
        $repository->method('findByEmail')->willReturn(null);
        self::assertNull((new Authenticator($repository))->attempt('unknown@example.com', 'password'));
    }

    public function testPasswordWhitespaceIsNotTrimmed(): void
    {
        $repository = $this->createStub(UserRepository::class);
        $repository->method('findByEmail')->willReturn(new User(1, 'Admin', 'admin@example.com', password_hash(' password ', PASSWORD_DEFAULT)));
        self::assertNull((new Authenticator($repository))->attempt('admin@example.com', 'password'));
        self::assertNotNull((new Authenticator($repository))->attempt('admin@example.com', ' password '));
    }
}
