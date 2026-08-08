<?php

declare(strict_types=1);

require dirname(__DIR__) . '/tests/bootstrap.php';

use Refatbd\GameAccountLookup\Registry\GameRegistry;

$root = dirname(__DIR__);
$registry = new GameRegistry();
$games = array_values($registry->all());
usort($games, static fn (array $a, array $b): int => strcasecmp((string) $a['label'], (string) $b['label']));
$verificationDates = [];
foreach ($games as $game) {
    foreach ((array) ($game['providerAvailability'] ?? []) as $audit) {
        if (is_array($audit) && !empty($audit['verifiedAt'])) {
            $verificationDates[] = (string) $audit['verifiedAt'];
        }
    }
}
sort($verificationDates);
$snapshotDate = $verificationDates === [] ? 'not-audited' : (string) end($verificationDates);

$availabilityCell = static function (array $game, string $provider): string {
    $audit = $game['providerAvailability'][$provider] ?? null;
    if (!is_array($audit)) {
        return 'Not audited';
    }

    $status = (string) ($audit['status'] ?? 'unknown');
    $labels = [
        'available' => 'Available',
        'maintenance' => 'Maintenance',
        'not-listed' => 'Not listed',
        'voucher-or-external' => 'Voucher/external only',
    ];
    $label = $labels[$status] ?? $status;
    $url = (string) ($audit['productUrl'] ?? $audit['productPage'] ?? '');
    if ($url !== '') {
        $label = '[' . $label . '](' . $url . ')';
    }

    if ($provider === 'codashop' && $status === 'available') {
        $dynamic = $game['providers']['codashop_dynamic'] ?? null;
        $classic = $game['providers']['codashop'] ?? null;
        $dynamicEnabled = is_array($dynamic) && ($dynamic['enabled'] ?? true) === true;
        $classicEnabled = is_array($classic) && ($classic['enabled'] ?? true) === true;
        $route = $dynamicEnabled ? 'dynamic regional' : 'classic';
        if ($classicEnabled && $dynamicEnabled) {
            $route .= ' + legacy fallback';
        }
        $label .= '<br><sub>' . $route . '</sub>';
    }
    if ($provider === 'gopaygames' && $status === 'available') {
        $label .= '<br><sub>page preflight + account API</sub>';
    }

    return $label;
};

$rows = [];
$providerRows = [];
foreach ($games as $game) {
    $providerConfigs = (array) ($game['providers'] ?? []);
    $other = [];
    foreach ($providerConfigs as $key => $config) {
        if (in_array($key, ['codashop', 'codashop_dynamic', 'gopaygames'], true)) {
            continue;
        }
        $other[] = '`' . $key . '`' . ((is_array($config) && ($config['enabled'] ?? true) !== true) ? ' (disabled)' : '');
    }

    $serverValues = array_values(array_unique(array_map(
        static fn (mixed $server): string => (string) $server,
        (array) ($game['servers'] ?? []),
    )));

    $rows[] = sprintf(
        '| %s | `%s` | %s | %s | %s | %s | %s | `%s` | %s |',
        str_replace('|', '\\|', (string) $game['label']),
        (string) $game['code'],
        ($game['requiresZone'] ?? false) ? 'Yes' : 'No',
        !($game['requiresZone'] ?? false)
            ? '—'
            : ($serverValues === [] ? 'Free-form' : implode(', ', array_map(static fn (string $item): string => '`' . $item . '`', $serverValues))),
        $availabilityCell($game, 'codashop'),
        $availabilityCell($game, 'gopaygames'),
        $other === [] ? '—' : implode(', ', $other),
        (string) $game['status'],
        ($game['aliases'] ?? []) === [] ? '—' : implode(', ', array_map(static fn (string $item): string => '`' . $item . '`', $game['aliases'])),
    );

    foreach (['codashop', 'gopaygames'] as $provider) {
        $audit = $game['providerAvailability'][$provider] ?? [];
        $providerRows[] = sprintf(
            '| %s | `%s` | `%s` | `%s` | %s | %s |',
            str_replace('|', '\\|', (string) $game['label']),
            (string) $game['code'],
            $provider,
            (string) ($audit['status'] ?? 'not-audited'),
            (string) ($audit['verifiedAt'] ?? '—'),
            isset($audit['productUrl']) || isset($audit['productPage'])
                ? '[Official page](' . (string) ($audit['productUrl'] ?? $audit['productPage']) . ')'
                : '—',
        );
    }
}

$table = implode("\n", [
    '| Game | Code | Zone Required | Known Server Values | Codashop | GoPay | Other | Status | Aliases |',
    '|---|---|:---:|---|---|---|---|---|---|',
    ...$rows,
]);

$active = count(array_filter($games, static fn (array $game): bool => ($game['status'] ?? 'active') === 'active'));
$codashopAvailable = count(array_filter($games, static fn (array $game): bool => ($game['providerAvailability']['codashop']['status'] ?? null) === 'available'));
$gopayAvailable = count(array_filter($games, static fn (array $game): bool => ($game['providerAvailability']['gopaygames']['status'] ?? null) === 'available'));
$gopayMaintenance = count(array_filter($games, static fn (array $game): bool => ($game['providerAvailability']['gopaygames']['status'] ?? null) === 'maintenance'));
$summary = sprintf(
    '> Provider audit snapshot through (**%s**): **%d game definitions**, **%d active**, **%d Codashop direct-ID listings**, **%d GoPay direct-ID listings**, and **%d GoPay listings under maintenance**.',
    $snapshotDate,
    count($games),
    $active,
    $codashopAvailable,
    $gopayAvailable,
    $gopayMaintenance,
);

$readmePath = $root . '/README.md';
$readme = file_get_contents($readmePath);
if ($readme === false) {
    throw new RuntimeException('Could not read README.md');
}
$start = '<!-- supported-games:start -->';
$end = '<!-- supported-games:end -->';
$replacement = $start . "\n\n" . $summary . "\n\n" . $table . "\n\n" . $end;
$pattern = '/' . preg_quote($start, '/') . '.*?' . preg_quote($end, '/') . '/s';
$updated = preg_replace($pattern, $replacement, $readme, 1, $count);
if ($updated === null || $count !== 1) {
    throw new RuntimeException('README supported-game markers were not found exactly once.');
}
$updated = str_replace(["\r\n", "\r"], "\n", $updated);
file_put_contents($readmePath, $updated);

$docs = <<<MD
# Supported Games

This file is generated from [`resources/games.php`](../resources/games.php) plus the dated availability catalog in [`resources/provider-catalog.php`](../resources/provider-catalog.php). Do not edit the table by hand.

```bash
composer docs:games
```

{$summary}

## Status meanings

| Status | Meaning |
|---|---|
| `active` | At least one currently audited bundled provider route is enabled. |
| `provider-unavailable` | Previously supported metadata remains for reference, but no currently verified bundled provider route is enabled. |
| `external-provider-required` | A separate maintained/authorized adapter is required. |

## Provider availability meanings

| Provider state | Meaning |
|---|---|
| `available` | An official current product page/direct-ID route was verified during the dated audit. It is still not an uptime guarantee. |
| `maintenance` | The official product page exists but currently reports maintenance; the adapter is disabled. |
| `not-listed` | No current direct product page was verified; stale adapters are disabled. |
| `voucher-or-external` | A store presence exists, but it is not a bundled direct nickname-validation flow. |

## Registry table

{$table}

## Availability policy

The catalog is a dated snapshot, not a permanent guarantee. Provider pages, SKU metadata, internal codes, regional restrictions and account-validation behavior can change without notice. Before publishing a release, run the provider audit tool and verify permitted valid/invalid test accounts. Never commit cookies, access tokens, personal account data or short-lived JWT values.
MD;
file_put_contents($root . '/docs/SUPPORTED_GAMES.md', str_replace(["\r\n", "\r"], "\n", $docs) . "\n");

$providerTable = implode("\n", [
    '| Game | Code | Provider | Audit state | Verified | Official evidence |',
    '|---|---|---|---|---|---|',
    ...$providerRows,
]);
$availabilityDoc = <<<MD
# Provider Availability Audit

**Latest verification date:** {$snapshotDate}  
**Maintainer:** [RefatBD](https://github.com/refatbd)

This document records the current public-storefront audit used by the default registry. The machine-readable source is [`resources/provider-catalog.php`](../resources/provider-catalog.php).

{$summary}

## What changed in v0.7.0

- Every currently verified Codashop direct-ID title now uses runtime product-page metadata discovery and regional storefront retry.
- Legacy fixed Codashop price-point metadata is retained only as a final compatibility fallback.
- The Codashop audit now checks the global country index and sitemap so a product missing in one country is not treated as globally unavailable.
- Added Codashop direct-ID routes for AU2 Mobile, Free Fire MAX, Honkai Impact 3, Magic Chess: Go Go and Metal Slug: Awakening.
- Updated Ragnarok M to the current `ragnarok-m-eternal-love-big-cat-coin` route and removed obsolete regional fallbacks.
- Aether Gazer, Asphalt 9, BarbarQ and EOS RED are no longer marked Codashop-available because no current global product route was found.
- Honor of Kings remains present on Codashop, but is classified as voucher/external because its regional pages deliver redeem codes instead of validating a Player ID.
- Current GoPay product pages are preflighted before account validation; product codes can be discovered from Next.js/RSC metadata.
- GoPay products that publicly report maintenance are disabled instead of producing misleading player errors.
- Badlanders, Super Mecha Champions and War Planet Online are no longer marked active because no current direct product listing was verified.
- PUBG Mobile's Codashop presence is classified as voucher/external rather than a bundled direct nickname-validation route.

## Latest GoPay audit additions

- Added direct account-validation routes for Free Fire MAX, Honkai Impact 3 and Metal Slug: Awakening.
- Updated Call of Duty Mobile, Mobile Legends and PUBG Mobile to their current official product slugs; PUBG Mobile now uses the current `PUBGM` code.
- Current RSC products whose code is `NOVERIFY` are classified as voucher/external and are not enabled as nickname providers.

## Audit methodology

1. Enumerate Codashop's global country index and current sitemap instead of relying on one regional homepage.
2. Confirm a current official product page in every retained fallback region.
3. Inspect whether the page accepts a Player/User/Role ID (and Zone ID where required) or only sells a voucher/redeem code.
4. Inspect the live GoPay RSC product code; an account form using `NOVERIFY` is not a nickname-validation route.
5. Classify direct-ID, maintenance, voucher/external, or not-listed status.
6. Enable only direct-ID routes in the default registry.
7. Keep all evidence dates and URLs in the machine-readable catalog.

This is a storefront-availability audit. It does not submit purchases and does not claim an official public nickname API.

## Complete provider matrix

{$providerTable}

## Refreshing the snapshot

```bash
php tools/audit-provider-catalog.php
php tools/audit-provider-catalog.php --json
php tools/audit-provider-catalog.php --live
composer docs:games
```

`--live` checks public product pages only. It does not submit a player ID or purchase. Review changes manually before editing `resources/provider-catalog.php`.
MD;
file_put_contents($root . '/docs/PROVIDER_AVAILABILITY.md', str_replace(["\r\n", "\r"], "\n", $availabilityDoc) . "\n");

echo sprintf("Generated provider-aware tables for %d games.\n", count($games));
