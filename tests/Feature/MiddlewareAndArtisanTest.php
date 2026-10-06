<?php

declare(strict_types=1);

namespace Flagmint\Laravel\Tests\Feature;

use Flagmint\Laravel\Context\RequestContext;
use Flagmint\Laravel\Facades\Flagmint;
use Flagmint\Laravel\Middleware\SetFlagmintContext;
use Flagmint\Laravel\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;

final class MiddlewareAndArtisanTest extends TestCase
{
    public function testMiddlewareSetsPerRequestContext(): void
    {
        $this->bindMockedClient();

        Route::middleware(SetFlagmintContext::class)->get('/flag-check', function (RequestContext $ctx) {
            return response()->json([
                'context' => $ctx->get(),
                'enabled' => Flagmint::bool('new-checkout', false),
            ]);
        });

        $user = new class implements Authenticatable {
            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier(): mixed
            {
                return 'user-99';
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

        $this->actingAs($user)->getJson('/flag-check')
            ->assertOk()
            ->assertJsonPath('context.key', 'user-99')
            ->assertJsonPath('enabled', true);

        $other = new class implements Authenticatable {
            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier(): mixed
            {
                return 'user-1';
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

        $this->actingAs($other)->getJson('/flag-check')
            ->assertOk()
            ->assertJsonPath('context.key', 'user-1');
    }

    public function testArtisanRefresh(): void
    {
        $this->bindMockedClient();
        $this->artisan('flagmint:refresh')->assertSuccessful();
    }
}
