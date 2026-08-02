<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Refatbd\GameAccountLookup\GameAccountLookup;

final class GameAccountLookupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__, 2) . '/config/game-account-lookup.php',
            'game-account-lookup',
        );

        $this->app->singleton('game-account-lookup', function (Application $app): GameAccountLookup {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('game-account-lookup', []);
            $cacheConfig = (array) ($config['cache'] ?? []);
            $cache = null;

            if (($cacheConfig['enabled'] ?? true) === true) {
                $store = $cacheConfig['store'] ?? null;
                $repository = $store
                    ? $app['cache']->store((string) $store)
                    : $app['cache']->store();
                $cache = new LaravelCache($repository);
            }

            $lookup = GameAccountLookup::make([
                'timeout' => (int) ($config['timeout'] ?? 12),
                'connect_timeout' => (int) ($config['connect_timeout'] ?? 5),
                'verify_tls' => (bool) ($config['verify_tls'] ?? true),
                'debug' => (bool) ($config['debug'] ?? false),
                'cache' => $cache,
                'cache_ttl' => (int) ($cacheConfig['ttl'] ?? 300),
                'logger' => static function (string $event, array $context) use ($app): void {
                    if ((bool) $app['config']->get('game-account-lookup.debug', false)) {
                        $app['log']->debug($event, $context);
                    }
                },
            ]);

            foreach ((array) ($config['games'] ?? []) as $code => $definition) {
                if (is_array($definition)) {
                    $lookup->registerGame((string) $code, $definition);
                }
            }

            return $lookup;
        });

        $this->app->alias('game-account-lookup', GameAccountLookup::class);
    }

    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__, 2) . '/config/game-account-lookup.php'
                => config_path('game-account-lookup.php'),
        ], 'game-account-lookup-config');
    }
}
