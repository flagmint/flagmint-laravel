<?php

declare(strict_types=1);

namespace Flagmint\Laravel\Tests\Unit;

use Flagmint\Cache\RulesSnapshot;
use Flagmint\Laravel\Cache\LaravelCacheAdapter;
use Flagmint\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

final class LaravelCacheAdapterTest extends TestCase
{
    public function testRoundTripViaArrayCache(): void
    {
        config(['cache.default' => 'array']);
        $adapter = new LaravelCacheAdapter(Cache::store('array'));
        $snapshot = new RulesSnapshot(7, (int) (microtime(true) * 1000) + 60_000, [['key' => 'x']], []);
        $adapter->saveRulesSnapshot('fm', $snapshot);
        $loaded = $adapter->loadRulesSnapshot('fm');
        $this->assertNotNull($loaded);
        $this->assertSame(7, $loaded->version);
    }
}
