<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Registry;

/**
 * Applies the dated provider-storefront audit to the legacy game registry.
 *
 * resources/games.php retains parser hints and legacy fallback metadata.
 * resources/provider-catalog.php is the current source of truth for whether a
 * provider/product is available and how its live storefront should be routed.
 */
final class ProviderCatalog
{
    /** @var array<string, array<string, array<string, mixed>>> */
    private array $catalog;

    /**
     * @param array<string, array<string, array<string, mixed>>>|null $catalog
     */
    public function __construct(?array $catalog = null)
    {
        /** @var array<string, array<string, array<string, mixed>>> $defaults */
        $defaults = require dirname(__DIR__, 2) . '/resources/provider-catalog.php';
        $this->catalog = $catalog ?? $defaults;
    }

    /**
     * @param array<string, array<string, mixed>> $games
     * @return array<string, array<string, mixed>>
     */
    public function apply(array $games): array
    {
        foreach ($games as $code => &$game) {
            $audit = $this->catalog[$code] ?? [];
            $game['providerAvailability'] = $audit;
            $game['providerAuditVerifiedAt'] = $this->latestVerificationDate($audit);
            $game['providers'] = is_array($game['providers'] ?? null) ? $game['providers'] : [];

            $this->applyCodashop($game, $audit['codashop'] ?? null);
            $this->applyGopay($game, $audit['gopaygames'] ?? null);
            $this->finalizeStatus($game);
        }
        unset($game);

        return $games;
    }

    /** @return array<string, array<string, array<string, mixed>>> */
    public function all(): array
    {
        return $this->catalog;
    }

    /** @param array<string, mixed> $game @param array<string, mixed>|null $audit */
    private function applyCodashop(array &$game, ?array $audit): void
    {
        if ($audit === null) {
            return;
        }

        $status = (string) ($audit['status'] ?? 'not-listed');
        $providers = (array) $game['providers'];
        $classic = is_array($providers['codashop'] ?? null) ? $providers['codashop'] : [];
        $existingDynamic = is_array($providers['codashop_dynamic'] ?? null) ? $providers['codashop_dynamic'] : [];

        if ($status !== 'available') {
            if ($classic !== []) {
                $classic['enabled'] = false;
                $classic['availabilityStatus'] = $status;
                $providers['codashop'] = $classic;
            }
            if ($existingDynamic !== []) {
                $existingDynamic['enabled'] = false;
                $existingDynamic['availabilityStatus'] = $status;
                $providers['codashop_dynamic'] = $existingDynamic;
            }
            $game['providers'] = $providers;
            return;
        }

        $dynamic = [
            'enabled' => true,
            'availabilityStatus' => 'available',
            'verifiedAt' => $audit['verifiedAt'] ?? null,
            'productUrl' => $audit['productUrl'] ?? null,
            'pageSlugs' => array_values(array_filter((array) ($audit['pageSlugs'] ?? []), 'is_string')),
            'storefronts' => array_values((array) ($audit['storefronts'] ?? [])),
            'maxProfiles' => (int) ($audit['maxProfiles'] ?? 10),
        ];

        // Carry only parser/form behavior into runtime discovery. Do not copy
        // fixed SKU, price-point, token, or payment-channel values here.
        foreach ([
            'voucherTypeName',
            'nicknamePaths',
            'serverPath',
            'zone',
            'zoneField',
            'zoneFilter',
            'extraForm',
            'invalidMessage',
            'requireOrderToken',
        ] as $key) {
            if (array_key_exists($key, $existingDynamic)) {
                $dynamic[$key] = $existingDynamic[$key];
            } elseif (array_key_exists($key, $classic)) {
                $dynamic[$key] = $classic[$key];
            }
        }

        // Explicit profile-specific overrides from the audit or previous
        // version still win over generated locale/slug combinations.
        foreach (['profiles', 'pageUrl'] as $key) {
            if (array_key_exists($key, $audit)) {
                $dynamic[$key] = $audit[$key];
            }
        }

        if ($dynamic['pageSlugs'] === [] && isset($existingDynamic['pageSlug'])) {
            $dynamic['pageSlugs'] = [(string) $existingDynamic['pageSlug']];
        }

        $classic['enabled'] = $classic !== [];
        $classic['availabilityStatus'] = 'legacy-fallback';
        $classic['verifiedAt'] = $audit['verifiedAt'] ?? null;

        // Build the complete provider set here. GameRegistry applies the final
        // hosting-safe execution priority when it registers the definition.
        $ordered = [];
        if (isset($providers['codashop_browser'])) {
            $ordered['codashop_browser'] = $providers['codashop_browser'];
        }
        $ordered['codashop_dynamic'] = $dynamic;
        foreach ($providers as $key => $config) {
            if (in_array($key, ['codashop_browser', 'codashop_dynamic', 'codashop'], true)) {
                continue;
            }
            $ordered[(string) $key] = $config;
        }
        if ($classic !== []) {
            $ordered['codashop'] = $classic;
        }

        $game['providers'] = $ordered;
    }

    /** @param array<string, mixed> $game @param array<string, mixed>|null $audit */
    private function applyGopay(array &$game, ?array $audit): void
    {
        if ($audit === null) {
            return;
        }

        $providers = (array) $game['providers'];
        $existing = is_array($providers['gopaygames'] ?? null) ? $providers['gopaygames'] : [];
        $status = (string) ($audit['status'] ?? 'not-listed');

        if ($status !== 'available') {
            if ($existing !== [] || $status === 'maintenance') {
                $providers['gopaygames'] = array_replace($existing, [
                    'enabled' => false,
                    'availabilityStatus' => $status,
                    'verifiedAt' => $audit['verifiedAt'] ?? null,
                    'productPage' => $audit['productPage'] ?? ($existing['productPage'] ?? null),
                    'availabilityNote' => $audit['note'] ?? null,
                ]);
            }
            $game['providers'] = $providers;
            return;
        }

        $config = array_replace($existing, [
            'enabled' => true,
            'availabilityStatus' => 'available',
            'verifiedAt' => $audit['verifiedAt'] ?? null,
            'productPage' => $audit['productPage'] ?? ($existing['productPage'] ?? null),
            'availabilityNote' => $audit['note'] ?? null,
        ]);
        if (isset($audit['code'])) {
            $config['code'] = (string) $audit['code'];
        }
        if (isset($audit['codeCandidates'])) {
            $config['codeCandidates'] = array_values(array_filter((array) $audit['codeCandidates'], 'is_string'));
        }

        // Keep the provider in its existing position while applying audit
        // metadata. GameRegistry applies the final hosting-safe priority.
        if (array_key_exists('gopaygames', $providers)) {
            $providers['gopaygames'] = $config;
        } else {
            $ordered = [];
            foreach ($providers as $key => $provider) {
                if ($key === 'codashop') {
                    $ordered['gopaygames'] = $config;
                }
                $ordered[$key] = $provider;
            }
            if (!isset($ordered['gopaygames'])) {
                $ordered['gopaygames'] = $config;
            }
            $providers = $ordered;
        }

        $game['providers'] = $providers;
    }

    /** @param array<string, mixed> $game */
    private function finalizeStatus(array &$game): void
    {
        $enabled = [];
        foreach ((array) ($game['providers'] ?? []) as $key => $config) {
            if (!is_array($config) || ($config['enabled'] ?? true) === true) {
                $enabled[] = (string) $key;
            }
        }

        if ($enabled !== []) {
            // Provider audit can reactivate definitions that were previously
            // disabled only because SKU metadata had expired.
            if (in_array((string) ($game['status'] ?? ''), ['metadata-refresh-required', 'provider-unavailable'], true)) {
                $game['status'] = 'active';
            }
            return;
        }

        if (($game['status'] ?? 'active') === 'active') {
            $game['status'] = 'provider-unavailable';
        }

        $notes = [];
        foreach ((array) ($game['providerAvailability'] ?? []) as $provider => $audit) {
            if (!is_array($audit)) {
                continue;
            }
            $status = (string) ($audit['status'] ?? 'unknown');
            if ($status === 'available') {
                continue;
            }
            $notes[] = sprintf('%s: %s%s', $provider, $status, isset($audit['note']) ? ' — ' . $audit['note'] : '');
        }
        if ($notes !== []) {
            $existing = trim((string) ($game['notes'] ?? ''));
            $game['notes'] = trim($existing . ($existing !== '' ? ' ' : '') . implode(' ', $notes));
        }
    }

    /** @param array<string, array<string, mixed>> $audit */
    private function latestVerificationDate(array $audit): ?string
    {
        $dates = [];
        foreach ($audit as $entry) {
            if (is_array($entry) && is_string($entry['verifiedAt'] ?? null)) {
                $dates[] = $entry['verifiedAt'];
            }
        }

        if ($dates === []) {
            return null;
        }

        rsort($dates);
        return $dates[0];
    }
}
