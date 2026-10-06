<?php

declare(strict_types=1);

namespace Flagmint\Laravel;

use Flagmint\Cache\ArrayMemoryAdapter;
use Flagmint\Cache\CacheAdapter;
use Flagmint\Cache\PredisRedisClient;
use Flagmint\Cache\RedisAdapter;
use Flagmint\Client;
use Flagmint\Laravel\Blade\BladeDirectives;
use Flagmint\Laravel\Cache\LaravelCacheAdapter;
use Flagmint\Laravel\Console\RefreshCommand;
use Flagmint\Laravel\Context\RequestContext;
use Flagmint\Laravel\Middleware\SetFlagmintContext;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Predis\Client as PredisClient;
use Psr\Log\LoggerInterface;

/**
 * Registers Flagmint into a Laravel application.
 *
 * - Merges / publishes `config/flagmint.php`
 * - Binds singleton {@see Client}, {@see FlagmintManager}, {@see CacheAdapter}
 * - Registers Blade `@feature`, middleware alias `flagmint.context`, Artisan `flagmint:refresh`
 *
 * Install: `composer require flagmint/laravel` then
 * `php artisan vendor:publish --tag=flagmint-config`.
 *
 * **Octane note:** Client is a singleton (shared rules). Never mutate a shared
 * evaluation context — use {@see SetFlagmintContext} per request or pass `$context`
 * explicitly on each call.
 */
final class FlagmintServiceProvider extends ServiceProvider
{
    /**
     * Register container bindings and merge package config.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/flagmint.php', 'flagmint');

        $this->app->singleton(RequestContext::class);

        $this->app->singleton(CacheAdapter::class, function (Application $app): CacheAdapter {
            return $this->resolveCacheAdapter($app);
        });

        $this->app->singleton(Client::class, function (Application $app): Client {
            $config = $app['config']->get('flagmint', []);
            $apiKey = (string) ($config['api_key'] ?? '');
            if ($apiKey === '') {
                $apiKey = 'missing-flagmint-key';
            }

            $options = [
                'apiKey' => $apiKey,
                'enableFlagmint' => (bool) ($config['enable'] ?? true) && $apiKey !== 'missing-flagmint-key',
                'env' => (string) ($config['env'] ?? 'production'),
                'cacheAdapter' => $app->make(CacheAdapter::class),
                'onError' => static function (array $error) use ($app): void {
                    if ($app->bound(LoggerInterface::class)) {
                        $app->make(LoggerInterface::class)->warning('Flagmint error', $error);
                    }
                },
            ];
            if (!empty($config['rest_endpoint'])) {
                $options['restEndpoint'] = $config['rest_endpoint'];
            }
            if (!empty($config['handshake_endpoint'])) {
                $options['handshakeEndpoint'] = $config['handshake_endpoint'];
            }

            return new Client($options);
        });

        $this->app->singleton(FlagmintManager::class, function (Application $app): FlagmintManager {
            $config = $app['config']->get('flagmint', []);

            return new FlagmintManager(
                $app->make(Client::class),
                $app->make(RequestContext::class),
                (bool) ($config['queue_events'] ?? true),
            );
        });

        $this->app->alias(FlagmintManager::class, 'flagmint');
    }

    /**
     * Publish config, register Artisan commands, middleware alias, and Blade directives.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/flagmint.php' => config_path('flagmint.php'),
            ], 'flagmint-config');

            $this->commands([RefreshCommand::class]);
        }

        $this->app->make('router')->aliasMiddleware('flagmint.context', SetFlagmintContext::class);

        BladeDirectives::register();
    }

    /**
     * @param Application $app
     */
    private function resolveCacheAdapter(Application $app): CacheAdapter
    {
        $cache = $app['config']->get('flagmint.cache', []);
        $driver = (string) ($cache['driver'] ?? 'laravel');
        $prefix = (string) ($cache['prefix'] ?? 'flagmint:rules:');

        return match ($driver) {
            'memory' => new ArrayMemoryAdapter(),
            'redis' => $this->redisAdapter($cache, $prefix),
            'custom' => $this->customAdapter($cache),
            default => new LaravelCacheAdapter(
                Cache::store($cache['store'] ?? null),
                $prefix,
            ),
        };
    }

    /**
     * @param array<string, mixed> $cache
     */
    private function redisAdapter(array $cache, string $prefix): CacheAdapter
    {
        if (!class_exists(PredisClient::class)) {
            throw new \RuntimeException('predis/predis is required for FLAGMINT_CACHE_DRIVER=redis');
        }
        $url = (string) ($cache['redis_url'] ?? 'tcp://127.0.0.1:6379');

        return new RedisAdapter(new PredisRedisClient(new PredisClient($url)), $prefix);
    }

    /**
     * @param array<string, mixed> $cache
     */
    private function customAdapter(array $cache): CacheAdapter
    {
        $class = (string) ($cache['adapter'] ?? '');
        if ($class === '' || !class_exists($class)) {
            throw new \InvalidArgumentException('flagmint.cache.adapter must be a CacheAdapter class');
        }
        $adapter = new $class();
        if (!$adapter instanceof CacheAdapter) {
            throw new \InvalidArgumentException($class . ' must implement Flagmint\\Cache\\CacheAdapter');
        }

        return $adapter;
    }
}
