<?php

declare(strict_types=1);

namespace Flagmint\Laravel;

/**
 * Packaged Laravel wrapper identity (sent as wrapperName / wrapperVersion).
 */
final class Package
{
    public const NAME = 'flagmint-laravel';

    /** Keep in sync with CHANGELOG. */
    public const VERSION = '0.1.1';
}
