<?php

declare(strict_types=1);

namespace Flagmint\Laravel\Context;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * Per-request evaluation context bag for Laravel.
 *
 * Bound as a **scoped/singleton-per-app** service that {@see SetFlagmintContext}
 * overwrites at the start of each HTTP request. {@see \Flagmint\Laravel\FlagmintManager}
 * reads it when facade/manager methods omit `$context`.
 *
 * Important under Octane: never treat this as global user state across concurrent
 * requests without resetting it in middleware each request (the provided middleware does).
 */
final class RequestContext
{
    /** @var array<string, mixed>|null */
    private ?array $context = null;

    /**
     * Replace the context for the current request (or clear with null).
     *
     * @param array<string, mixed>|null $context
     */
    public function set(?array $context): void
    {
        $this->context = $context;
    }

    /**
     * Context previously set for this request, if any.
     *
     * @return array<string, mixed>|null
     */
    public function get(): ?array
    {
        return $this->context;
    }

    /**
     * Map an authenticated user into an OpenFeature-style user context.
     *
     * ```php
     * RequestContext::fromUser($request->user(), ['plan' => $user->plan]);
     * // → ['kind' => 'user', 'key' => '42', 'plan' => 'premium']
     * ```
     *
     * @param Authenticatable|null $user
     * @param array<string, mixed> $extra Extra attributes merged onto the context
     * @return array<string, mixed>
     */
    public static function fromUser(?Authenticatable $user, array $extra = []): array
    {
        if ($user === null) {
            return $extra === [] ? ['kind' => 'user', 'key' => 'anonymous'] : array_merge(['kind' => 'user'], $extra);
        }

        $key = method_exists($user, 'getAuthIdentifier')
            ? (string) $user->getAuthIdentifier()
            : 'anonymous';

        return array_merge([
            'kind' => 'user',
            'key' => $key,
        ], $extra);
    }

    /**
     * Build context from the current HTTP request's authenticated user.
     *
     * @param Request $request
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    public static function fromRequest(Request $request, array $extra = []): array
    {
        /** @var Authenticatable|null $user */
        $user = $request->user();

        return self::fromUser($user, $extra);
    }
}
