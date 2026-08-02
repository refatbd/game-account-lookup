<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Registry;

use InvalidArgumentException;
use Refatbd\GameAccountLookup\Support\Normalizer;

final class GameRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $games = [];

    /** @var array<string, string> */
    private array $aliases = [];

    /**
     * @param array<string, array<string, mixed>>|null $games
     */
    public function __construct(?array $games = null)
    {
        /** @var array<string, array<string, mixed>> $defaults */
        $defaults = require dirname(__DIR__, 2) . '/resources/games.php';
        if ($games === null) {
            $defaults = (new ProviderCatalog())->apply($defaults);
        }

        foreach ($games ?? $defaults as $code => $definition) {
            $this->register((string) $code, $definition);
        }
    }

    /**
     * @param array<string, mixed> $definition
     */
    public function register(string $code, array $definition): self
    {
        $canonical = Normalizer::gameCode($code);

        if ($canonical === '') {
            throw new InvalidArgumentException('Game code cannot be empty.');
        }

        $definition['code'] = $canonical;
        $definition['label'] ??= $code;
        $definition['aliases'] ??= [];
        $definition['providers'] = $this->prioritizeProviders(
            is_array($definition['providers'] ?? null) ? $definition['providers'] : [],
        );
        $definition['requiresZone'] ??= false;
        $definition['status'] ??= 'active';

        $this->games[$canonical] = $definition;
        $this->aliases[$canonical] = $canonical;
        $this->aliases[Normalizer::gameCode((string) $definition['label'])] = $canonical;

        foreach ((array) $definition['aliases'] as $alias) {
            $this->alias((string) $alias, $canonical);
        }

        return $this;
    }

    public function alias(string $alias, string $canonical): self
    {
        $alias = Normalizer::gameCode($alias);
        $canonical = Normalizer::gameCode($canonical);

        if ($alias !== '') {
            $this->aliases[$alias] = $canonical;
        }

        return $this;
    }

    public function resolve(string $input): ?string
    {
        $key = Normalizer::gameCode($input);

        return $this->aliases[$key] ?? (isset($this->games[$key]) ? $key : null);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $input): ?array
    {
        $canonical = $this->resolve($input);

        return $canonical !== null ? ($this->games[$canonical] ?? null) : null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->games;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $result = [];

        foreach ($this->games as $game) {
            $providerKeys = [];
            foreach ((array) ($game['providers'] ?? []) as $key => $config) {
                if (!is_array($config) || ($config['enabled'] ?? true) === true) {
                    $providerKeys[] = (string) $key;
                }
            }

            $result[] = [
                'code' => $game['code'],
                'label' => $game['label'],
                'aliases' => $game['aliases'],
                'requires_zone' => (bool) $game['requiresZone'],
                'status' => $game['status'],
                'providers' => $providerKeys,
                'servers' => array_values(array_unique(array_map(
                    static fn (mixed $server): string => (string) $server,
                    (array) ($game['servers'] ?? []),
                ))),
                'notes' => $game['notes'] ?? null,
                'provider_audit_verified_at' => $game['providerAuditVerifiedAt'] ?? null,
                'provider_availability' => $game['providerAvailability'] ?? [],
            ];
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public function search(string $query): array
    {
        $needle = Normalizer::gameCode($query);
        $matches = [];

        foreach ($this->games as $code => $game) {
            $haystacks = [
                $code,
                Normalizer::gameCode((string) ($game['label'] ?? $code)),
                ...array_map(
                    static fn (mixed $value): string => Normalizer::gameCode((string) $value),
                    (array) ($game['aliases'] ?? []),
                ),
            ];

            foreach ($haystacks as $haystack) {
                if (str_contains($haystack, $needle)) {
                    $matches[$code] = (string) ($game['label'] ?? $code);
                    break;
                }
            }
        }

        return $matches;
    }

    /**
     * Keep automatic fallback friendly to restricted/shared hosting.
     *
     * Direct PHP HTTP providers run first, regional discovery follows, and
     * providers that need a local browser/process always run last. Providers
     * outside the bundled set retain their configured relative order.
     *
     * @param array<string, mixed> $providers
     * @return array<string, mixed>
     */
    private function prioritizeProviders(array $providers): array
    {
        $ranked = [];

        foreach ($providers as $index => $config) {
            $key = (string) $index;
            $priority = match ($key) {
                'garena' => 0,
                'midasbuy' => 5,
                'gopaygames' => 10,
                'codashop' => 20,
                'codashop_dynamic' => 80,
                default => str_ends_with($key, '_browser') ? 100 : 50,
            };

            $ranked[] = [
                'key' => $key,
                'config' => $config,
                'priority' => $priority,
                'position' => count($ranked),
            ];
        }

        usort($ranked, static fn (array $left, array $right): int =>
            [$left['priority'], $left['position']] <=> [$right['priority'], $right['position']]
        );

        $ordered = [];
        foreach ($ranked as $provider) {
            $ordered[$provider['key']] = $provider['config'];
        }

        return $ordered;
    }
}
