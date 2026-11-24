<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

class CsrfTest extends Unit
{
    public function testTokenIsMintedOnceAndKept(): void
    {
        $session = [];
        $csrf = new \Security\Csrf($session);

        $first = $csrf->token();

        $this->assertSame(64, strlen($first));
        $this->assertSame($first, $csrf->token());
        $this->assertSame($first, $session['csrf_token']);
    }

    public function testOnlyTheSessionsTokenIsValid(): void
    {
        $session = [];
        $csrf = new \Security\Csrf($session);

        $this->assertFalse($csrf->isValid('anything'), 'no token minted yet');
        $token = $csrf->token();
        $this->assertTrue($csrf->isValid($token));
        $this->assertFalse($csrf->isValid($token . 'x'));
        $this->assertFalse($csrf->isValid(''));
        $this->assertFalse($csrf->isValid(null));
        $this->assertFalse($csrf->isValid([$token]));
    }

    public function testFieldIsAHiddenInputWithTheToken(): void
    {
        $session = ['csrf_token' => 'abc"def'];
        $csrf = new \Security\Csrf($session);

        $this->assertSame(
            '<input type="hidden" name="csrf_token" value="abc&quot;def">',
            $csrf->field(),
        );
    }
}
