<?php

declare(strict_types=1);

namespace Flagmint\Laravel\Facades;

use Flagmint\Laravel\FlagmintManager;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for {@see FlagmintManager}.
 *
 * ```php
 * use Flagmint\Laravel\Facades\Flagmint;
 *
 * Flagmint::ready();
 *
 * if (Flagmint::bool('new-checkout')) { … }
 * if (Flagmint::isEnabled('new-checkout', ['kind' => 'user', 'key' => $id])) { … }
 *
 * Flagmint::track('new-checkout', ['action' => 'click']);
 * ```
 *
 * Register middleware `flagmint.context` (or {@see \Flagmint\Laravel\Middleware\SetFlagmintContext})
 * so omitted `$context` arguments use the authenticated user for that request.
 *
 * @method static bool ready()
 * @method static void refresh()
 * @method static bool isEnabled(string $flagKey, ?array $context = null, bool $fallback = false)
 * @method static mixed getFlag(string $flagKey, mixed $fallback = null, ?array $context = null)
 * @method static bool bool(string $flagKey, bool $fallback = false, ?array $context = null)
 * @method static string string(string $flagKey, string $fallback = '', ?array $context = null)
 * @method static int|float number(string $flagKey, int|float $fallback = 0, ?array $context = null)
 * @method static mixed json(string $flagKey, mixed $fallback = null, ?array $context = null)
 * @method static void track(string $flagKey, array $properties = [])
 * @method static void trackError(string $flagKey, array $properties = [])
 * @method static bool flushEvents()
 * @method static \Flagmint\FlagmintClient client()
 *
 * @see FlagmintManager
 */
final class Flagmint extends Facade
{
    /**
     * @return class-string
     */
    protected static function getFacadeAccessor(): string
    {
        return FlagmintManager::class;
    }
}
