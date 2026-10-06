<?php

declare(strict_types=1);

namespace Flagmint\Laravel\Cache;

use Flagmint\Cache\CacheAdapter;
use Flagmint\Cache\RulesSnapshot;
use Illuminate\Contracts\Cache\Repository;

/**
 * Default Laravel {@see CacheAdapter} — stores rules in the app cache store.
 *
 * Uses whatever driver `CACHE_STORE` / `FLAGMINT_CACHE_STORE` points at (Redis,
 * Memcached, database, …) so FPM workers share one snapshot without a custom
 * Redis client. Configured by {@see \Flagmint\Laravel\FlagmintServiceProvider}
 * when `flagmint.cache.driver` is `laravel` (the default).
 */
final class LaravelCacheAdapter implements CacheAdapter
{
    /**
     * @param Repository $cache Laravel cache repository (`Cache::store(…)`)
     * @param string $prefix Key prefix before the hashed API key
     */
    public function __construct(
        private readonly Repository $cache,
        private readonly string $prefix = 'flagmint:rules:',
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function loadRulesSnapshot(string $apiKey): ?RulesSnapshot
    {
        $raw = $this->cache->get($this->key($apiKey));
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        try {
            return RulesSnapshot::fromJson($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     *
     * TTL is lease expiry + 5 minutes for eviction; FlagmintClient still fail-closes on lease.
     */
    public function saveRulesSnapshot(string $apiKey, RulesSnapshot $snapshot): void
    {
        $ttlSeconds = max(1, (int) ceil(($snapshot->expiresAt / 1000) - time()) + 300);
        $this->cache->put($this->key($apiKey), $snapshot->toJson(), $ttlSeconds);
    }

    /**
     * @param string $apiKey
     */
    private function key(string $apiKey): string
    {
        return $this->prefix . hash('sha256', $apiKey);
    }
}
