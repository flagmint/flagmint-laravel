<?php

declare(strict_types=1);

namespace Flagmint\Laravel;

use Flagmint\Client;
use Flagmint\Laravel\Context\RequestContext;
use Flagmint\Laravel\Jobs\FlushFlagmintEvents;

/**
 * Laravel-facing API over {@see Client} with per-request context defaults.
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
 * set by middleware — never a process-global mutable Client context.
 */
final class FlagmintManager
{
    /**
     * @param Client $client Shared singleton client (rules store + cache)
     * @param RequestContext $requestContext Per-request context holder
     * @param bool $queueEvents When true, {@see track()} / {@see trackError()}
     *     dispatch {@see FlushFlagmintEvents}; when false, flush synchronously
     */
    public function __construct(
        private readonly Client $client,
        private readonly RequestContext $requestContext,
        private readonly bool $queueEvents = true,
    ) {
    }

    /**
     * Underlying core client (advanced: custom refresh, rules inspection).
     */
    public function client(): Client
    {
        return $this->client;
    }

    /**
     * Bootstrap handshake + rules refresh. See {@see Client::ready()}.
     */
    public function ready(): bool
    {
        return $this->client->ready();
    }

    /**
     * Pull latest config-sync rules. See {@see Client::refresh()}.
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
     * Evaluate any flag type. See {@see Client::getFlag()}.
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
     * Immediately POST any events still sitting on the Client buffer.
     */
    public function flushEvents(): bool
    {
        return $this->client->flushEvents();
    }

    /**
     * Drain the Client buffer and either flush now or dispatch {@see FlushFlagmintEvents}.
     *
     * Events are copied onto the job so a separate queue worker still sends them.
     */
    private function scheduleFlush(): void
    {
        $events = $this->client->getEventBuffer()->drain();
        if ($events === []) {
            return;
        }
        if (!$this->queueEvents) {
            foreach ($events as $event) {
                $kind = (string) ($event['kind'] ?? 'custom');
                $flagKey = (string) ($event['flagKey'] ?? '');
                $properties = is_array($event['properties'] ?? null) ? $event['properties'] : [];
                if ($kind === 'error') {
                    $this->client->trackError($flagKey, $properties);
                } else {
                    $this->client->track($flagKey, $properties);
                }
            }
            $this->client->flushEvents();

            return;
        }
        FlushFlagmintEvents::dispatch($events);
    }
}
