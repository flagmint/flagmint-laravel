<?php

declare(strict_types=1);

namespace Flagmint\Laravel\Tests;

use Flagmint\Cache\ArrayMemoryAdapter;
use Flagmint\Cache\CacheAdapter;
use Flagmint\FlagmintClient;
use Flagmint\Laravel\FlagmintServiceProvider;
use Flagmint\Laravel\Tests\Support\MockFlagmintHttp;
use GuzzleHttp\Psr7\HttpFactory;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [FlagmintServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('flagmint.api_key', 'fm_test_key');
        $app['config']->set('flagmint.enable', true);
        $app['config']->set('flagmint.cache.driver', 'memory');
        $app['config']->set('flagmint.queue_events', false);
        $app['config']->set('flagmint.rest_endpoint', 'https://example.test');
        $app['config']->set('flagmint.handshake_endpoint', 'https://example.test/auth/asl-handshake');
    }

    /**
     * Bind a FlagmintClient that uses mocked Flagmint HTTP + in-memory cache.
     *
     * @param list<array<string, mixed>>|null $payloads
     */
    protected function bindMockedClient(?array $payloads = null): FlagmintClient
    {
        $full = $payloads ?? [$this->fullConfigFixture()];
        $http = new MockFlagmintHttp($full);
        $factory = new HttpFactory();
        $adapter = new ArrayMemoryAdapter();

        $client = new FlagmintClient([
            'apiKey' => 'fm_test_key',
            'cacheAdapter' => $adapter,
            'httpClient' => $http,
            'requestFactory' => $factory,
            'streamFactory' => $factory,
            'restEndpoint' => 'https://example.test',
            'handshakeEndpoint' => 'https://example.test/auth/asl-handshake',
        ]);
        $client->ready();

        $this->app->instance(CacheAdapter::class, $adapter);
        $this->app->forgetInstance(FlagmintClient::class);
        $this->app->instance(FlagmintClient::class, $client);
        $this->app->forgetInstance(\Flagmint\Laravel\FlagmintManager::class);
        $this->app->forgetInstance('flagmint');

        return $client;
    }

    /**
     * @return array<string, mixed>
     */
    protected function fullConfigFixture(): array
    {
        $path = __DIR__ . '/fixtures/config/full_config.json';
        /** @var array<string, mixed> $data */
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }
}
