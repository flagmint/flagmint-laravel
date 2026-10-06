<?php

declare(strict_types=1);

namespace Flagmint\Laravel;

use Flagmint\FlagmintClient;
use Flagmint\Laravel\Context\RequestContext;
use Flagmint\Laravel\Jobs\FlushFlagmintEvents;

/**
 * Laravel-facing API over {@see FlagmintClient} with per-request context defaults.
 *
 * Prefer the {@see \Flagmint\Laravel\Facades\Flagmint} facade in app code:
 *
 * ```php
 * use Flagmint\Laravel\Facades\Flagmint;
 *
 * if (Flagmint::bool('new-checkout')) {
 *     // uses RequestContext from SetFlagmintContext middleware when present
 * }
 *
 * // Or pass context explicitly (recommended in jobs / CLI):
 * Flagmint::bool('new-checkout', false, ['kind' => 'user', 'key' => $id]);
 * ```
 *
 * When `$context` is omitted, the manager falls back to {@see RequestContext}
 * set by middleware — never a process-global mutable FlagmintClient context.
 */
final class FlagmintManager
{
    /**
     * @param FlagmintClient $client Shared singleton FlagmintClient (rules store + cache)
     * @param RequestContext $requestContext Per-request context holder
     * @param bool $queueEvents When true, {@see track()} / {@see trackError()}
     *     dispatch {@see FlushFlagmintEvents}; when false, flush synchronously
     */
    public function __construct(
        private readonly FlagmintClient $client,
        private readonly RequestContext $requestContext,
        private readonly bool $queueEvents = true,
    ) {
    }

    /**
     * Underlying core client (advanced: custom refresh, rules inspection).
     */
    public function client(): FlagmintClient
    {
        return $this->client;
    }

    /**
     * Bootstrap handshake + rules refresh. See {@see FlagmintClient::ready()}.
     */
    public function ready(): bool
    {
        return $this->client->ready();
    }

    /**
     * Pull latest config-sync rules. See {@see FlagmintClient::refresh()}.
     */
    public function refresh(): void
    {
        $this->client->refresh();
    }

    /**
     * Whether a boolean flag is on (dashboard-snippet friendly).
     *
     * @param string $flagKey Flag key
     * @param array<string, mixed>|null $context Explicit context, or request default
     * @param bool $fallback Safe default when eval cannot run
     */
    public function isEnabled(string $flagKey, ?array $context = null, bool $fallback = false): bool
    {
        return $this->client->isEnabled($flagKey, $context ?? $this->requestContext->get(), $fallback);
    }

    /**
     * Evaluate any flag type. See {@see FlagmintClient::getFlag()}.
     *
     * @param string $flagKey
     * @param mixed $fallback
     * @param array<string, mixed>|null $context
     */
    public function getFlag(string $flagKey, mixed $fallback = null, ?array $context = null): mixed
    {
        return $this->client->getFlag($flagKey, $fallback, $context ?? $this->requestContext->get());
    }

    /**
     * Evaluate a boolean flag using request context when `$context` is null.
     *
     * @param string $flagKey
     * @param bool $fallback
     * @param array<string, mixed>|null $context
     */
    public function bool(string $flagKey, bool $fallback = false, ?array $context = null): bool
    {
        return $this->client->bool($flagKey, $fallback, $context ?? $this->requestContext->get());
    }

    /**
     * Evaluate a string flag using request context when `$context` is null.
     *
     * @param string $flagKey
     * @param string $fallback
     * @param array<string, mixed>|null $context
     */
    public function string(string $flagKey, string $fallback = '', ?array $context = null): string
    {
        return $this->client->string($flagKey, $fallback, $context ?? $this->requestContext->get());
    }

    /**
     * Evaluate a number flag using request context when `$context` is null.
     *
     * @param string $flagKey
     * @param int|float $fallback
     * @param array<string, mixed>|null $context
     */
    public function number(string $flagKey, int|float $fallback = 0, ?array $context = null): int|float
    {
        return $this->client->number($flagKey, $fallback, $context ?? $this->requestContext->get());
    }

    /**
     * Evaluate a JSON flag using request context when `$context` is null.
     *
     * @param string $flagKey
     * @param mixed $fallback
     * @param array<string, mixed>|null $context
     */
    public function json(string $flagKey, mixed $fallback = null, ?array $context = null): mixed
    {
        return $this->client->json($flagKey, $fallback, $context ?? $this->requestContext->get());
    }

    /**
     * Buffer a custom event and schedule flush (queue or sync).
     *
     * @param string $flagKey
     * @param array<string, mixed> $properties
     */
    public function track(string $flagKey, array $properties = []): void
    {
        $this->client->track($flagKey, $properties);
        $this->scheduleFlush();
    }

    /**
     * Buffer an error event and schedule flush (queue or sync).
     *
     * @param string $flagKey
     * @param array<string, mixed> $properties
     */
    public function trackError(string $flagKey, array $properties = []): void
    {
        $this->client->trackError($flagKey, $properties);
        $this->scheduleFlush();
    }

    /**
     * Immediately POST any events still sitting on the FlagmintClient buffer.
     */
    public function flushEvents(): bool
    {
        return $this->client->flushEvents();
    }

    /**
     * Flush immediately (sync) or drain the buffer onto a queue job.
     *
     * Sync mode calls {@see FlagmintClient::flushEvents()} directly so timestamps and
     * kinds are unchanged. Queue mode drains once and dispatches the raw events
     * so the worker can POST them without re-recording.
     */
    private function scheduleFlush(): void
    {
        if (!$this->queueEvents) {
            $this->client->flushEvents();

            return;
        }

        $events = $this->client->drainPendingEvents();
        if ($events === []) {
            return;
        }

        FlushFlagmintEvents::dispatch($events);
    }
}
