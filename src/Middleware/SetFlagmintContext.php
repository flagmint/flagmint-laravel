<?php

declare(strict_types=1);

namespace Flagmint\Laravel\Middleware;

use Closure;
use Flagmint\Laravel\Context\RequestContext;
use Illuminate\Http\Request;

/**
 * Sets {@see RequestContext} from the authenticated user for this HTTP request.
 *
 * Register on routes that should inherit user targeting without passing context
 * on every `Flagmint::bool()` call:
 *
 * ```php
 * Route::middleware(['auth', 'flagmint.context'])->group(function () {
 *     // Flagmint::bool('x') uses kind=user, key=<auth id>
 * });
 * ```
 *
 * Alias `flagmint.context` is registered by {@see \Flagmint\Laravel\FlagmintServiceProvider}.
 */
final class SetFlagmintContext
{
    /**
     * @param RequestContext $requestContext Per-request context bag
     */
    public function __construct(private readonly RequestContext $requestContext)
    {
    }

    /**
     * @param Request $request
     * @param Closure(Request): mixed $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $this->requestContext->set(RequestContext::fromRequest($request));

        return $next($request);
    }
}
