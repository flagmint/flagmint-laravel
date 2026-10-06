<?php

declare(strict_types=1);

namespace Flagmint\Laravel\Tests\Feature;

use Flagmint\Laravel\Jobs\FlushFlagmintEvents;
use Flagmint\Laravel\Tests\TestCase;
use Flagmint\Tests\Integration\MockFlagmintHttp;
use Illuminate\Support\Facades\Bus;

final class FlushEventsJobTest extends TestCase
{
    public function testJobDispatchedWithEvents(): void
    {
        config(['flagmint.queue_events' => true]);
        Bus::fake();
        $client = $this->bindMockedClient();
        // Re-bind manager pickup queue_events=true — resolve fresh manager via facade after config
        $this->app->forgetInstance(\Flagmint\Laravel\FlagmintManager::class);

        \Flagmint\Laravel\Facades\Flagmint::track('new-checkout', ['a' => 1]);

        Bus::assertDispatched(FlushFlagmintEvents::class, function (FlushFlagmintEvents $job) {
            return count($job->events) === 1 && ($job->events[0]['flagKey'] ?? null) === 'new-checkout';
        });

        // Ensure client still usable
        $this->assertTrue($client->getRulesStore()->isReady());
    }

    public function testJobHandleFlushesViaClient(): void
    {
        $full = $this->fullConfigFixture();
        $http = new MockFlagmintHttp([$full]);
        $factory = new \GuzzleHttp\Psr7\HttpFactory();
        $client = new \Flagmint\Client([
            'apiKey' => 'fm_test_key',
            'httpClient' => $http,
            'requestFactory' => $factory,
            'streamFactory' => $factory,
            'restEndpoint' => 'https://example.test',
            'handshakeEndpoint' => 'https://example.test/auth/asl-handshake',
        ]);
        $client->ready();
        $this->app->instance(\Flagmint\Client::class, $client);

        $job = new FlushFlagmintEvents([
            ['flagKey' => 'new-checkout', 'kind' => 'custom', 'properties' => ['x' => 1]],
        ]);
        $job->handle($client);
        $this->assertSame(1, $http->getEventsFlushed());
    }
}
