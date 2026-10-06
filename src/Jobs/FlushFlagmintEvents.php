<?php

declare(strict_types=1);

namespace Flagmint\Laravel\Jobs;

use Flagmint\FlagmintClient;
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
 * The worker POSTs the payloads as-is via {@see FlagmintClient::flushEventBatch()} —
 * timestamps, kind, and properties are preserved (no re-track).
 *
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
     *     (`flagKey`, `kind`, `properties`, `timestamp`, …)
     */
    public function __construct(public readonly array $events)
    {
    }

    /**
     * POST the drained events without re-recording through track()/trackError().
     *
     * On failure throws so the queue can retry. Retries re-POST the same job
     * payloads (at-least-once); they do not re-buffer via track().
     *
     * @param FlagmintClient $client Container-bound FlagmintClient
     * @throws \RuntimeException When the evaluator rejects the batch
     */
    public function handle(FlagmintClient $client): void
    {
        if (!$client->flushEventBatch($this->events)) {
            throw new \RuntimeException('Flagmint event flush failed.');
        }
    }
}
