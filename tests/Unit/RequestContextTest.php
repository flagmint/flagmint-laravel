<?php

declare(strict_types=1);

namespace Flagmint\Laravel\Tests\Unit;

use Flagmint\Laravel\Context\RequestContext;
use Flagmint\Laravel\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;

final class RequestContextTest extends TestCase
{
    public function testFromUserMapsAuthIdentifier(): void
    {
        $user = new class implements Authenticatable {
            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier(): mixed
            {
                return 42;
            }

            public function getAuthPasswordName(): string
            {
                return 'password';
            }

            public function getAuthPassword(): string
            {
                return '';
            }

            public function getRememberToken(): ?string
            {
                return null;
            }

            public function setRememberToken($value): void
            {
            }

            public function getRememberTokenName(): string
            {
                return '';
            }
        };

        $ctx = RequestContext::fromUser($user, ['plan' => 'premium']);
        $this->assertSame('user', $ctx['kind']);
        $this->assertSame('42', $ctx['key']);
        $this->assertSame('premium', $ctx['plan']);
    }

    public function testHolderSetGet(): void
    {
        $holder = new RequestContext();
        $holder->set(['kind' => 'user', 'key' => 'a']);
        $this->assertSame('a', $holder->get()['key'] ?? null);
    }
}
