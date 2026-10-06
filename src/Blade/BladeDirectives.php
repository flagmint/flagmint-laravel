<?php

declare(strict_types=1);

namespace Flagmint\Laravel\Blade;

use Flagmint\Laravel\FlagmintManager;
use Illuminate\Support\Facades\Blade;

/**
 * Registers the `@feature` Blade conditional.
 *
 * ```blade
 * @feature('new-checkout')
 *   New checkout UI
 * @else
 *   Classic checkout
 * @endfeature
 * ```
 *
 * Resolution uses {@see FlagmintManager::bool()} (request context when middleware ran).
 * Optional second argument is the boolean fallback: `@feature('x', true)`.
 */
final class BladeDirectives
{
    /**
     * Register directives with lazy manager resolution (avoids boot-time Client init).
     */
    public static function register(): void
    {
        Blade::if('feature', static function (string $flagKey, bool $fallback = false): bool {
            return app(FlagmintManager::class)->bool($flagKey, $fallback);
        });
    }
}
