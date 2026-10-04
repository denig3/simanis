<?php
declare(strict_types=1);

namespace Tests;

use App\Http\Session;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testTokenPersistsAndRejectsMissingOrForgedValues(): void
    {
        $token = Session::token();
        self::assertSame(64, strlen($token));
        self::assertSame($token, Session::token());
        self::assertTrue(Session::validToken($token));
        self::assertFalse(Session::validToken(''));
        self::assertFalse(Session::validToken(str_repeat('x', 64)));
    }
}
