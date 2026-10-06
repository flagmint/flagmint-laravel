<?php

declare(strict_types=1);

namespace Flagmint\Laravel\Jobs;

use Flagmint\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queue job that POSTs Flagmint analytics events from a worker process.
 *
 * Events are serialized onto the job at dispatch time so a separate queue
 * worker (which does not share the web process EventBuffer) still sends them.
 * Dispatched automatically by {@see \Flagmint\Laravel\FlagmintManager} when
 * `flagmint.queue_events` is true.
 */
final class FlushFlagmintEvents implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param list<array<string, mixed>> $events Buffered event payloads
     *     (`flagKey`, `kind`, `properties`, …)
     */
    public function __construct(public readonly array $events)
    {
    }

    /**
     * Re-buffer events on the worker's Client and flush to the API.
     *
     * @param Client $client Container-bound Flagmint client
     */
    public function handle(Client $client): void
    {
        foreach ($this->events as $event) {
            $kind = (string) ($event['kind'] ?? 'custom');
            $flagKey = (string) ($event['flagKey'] ?? '');
            $properties = is_array($event['properties'] ?? null) ? $event['properties'] : [];
            if ($kind === 'error') {
                $client->trackError($flagKey, $properties);
            } else {
                $client->track($flagKey, $properties);
            }
        }
        $client->flushEvents();
    }
}
