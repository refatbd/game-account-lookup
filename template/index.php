<?php

declare(strict_types=1);

$config = require __DIR__ . '/config.php';

header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; connect-src 'self'; img-src 'self' data:; font-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Local browser test template for refatbd/game-account-lookup.">
    <title>Game Account Lookup Tester</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body data-demo-default="<?= ($config['demo_mode_default'] ?? false) ? '1' : '0' ?>">
<div class="ambient ambient-one"></div>
<div class="ambient ambient-two"></div>

<header class="site-header">
    <a class="brand" href="./" aria-label="Game Account Lookup home">
        <span class="brand-mark">GL</span>
        <span>
            <strong>Game Account Lookup</strong>
            <small>Local integration tester</small>
        </span>
    </a>
    <div class="status-cluster" aria-live="polite">
        <span id="apiStatus" class="status-pill status-pending"><span class="dot"></span>Checking API</span>
        <a class="github-link" href="https://github.com/refatbd" rel="noreferrer">Developer: RefatBD</a>
    </div>
</header>

<main class="page-shell">
    <section class="hero">
        <div>
            <p class="eyebrow">Framework-independent PHP package</p>
            <h1>Test player UID to nickname lookup before publishing.</h1>
            <p class="hero-copy">Choose any registered game, enter a player ID and optional zone/server, then inspect the normalized result, provider attempts and raw JSON.</p>
        </div>
        <div class="hero-stats" aria-label="Package statistics">
            <div><strong id="totalGames">—</strong><span>definitions</span></div>
            <div><strong id="activeGames">—</strong><span>active</span></div>
            <div><strong>PHP 8.1+</strong><span>runtime</span></div>
        </div>
    </section>

    <section class="workspace-grid">
        <article class="panel checker-panel">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Lookup form</p>
                    <h2>Account checker</h2>
                </div>
                <button id="loadSample" class="button button-ghost" type="button">Load sample</button>
            </div>

            <form id="lookupForm" novalidate>
                <label class="field">
                    <span>Search games</span>
                    <input id="gameSearch" type="search" placeholder="Free Fire, PUBG, MLBB…" autocomplete="off">
                </label>

                <label class="field">
                    <span>Game</span>
                    <select id="game" name="game" required>
                        <option value="">Loading supported games…</option>
                    </select>
                    <small id="gameHelp">Select a game to see provider and zone requirements.</small>
                </label>
                <div id="gameAvailability" class="availability-message hidden" role="status"></div>

                <div class="form-row">
                    <label class="field">
                        <span>Player ID / UID</span>
                        <input id="playerId" name="player_id" type="text" inputmode="text" maxlength="128" placeholder="4422076728" required>
                    </label>

                    <label id="zoneField" class="field hidden">
                        <span>Zone / Server ID</span>
                        <input id="zoneId" name="zone_id" type="text" maxlength="128" placeholder="Zone, server or shard">
                        <small id="zoneHelp"></small>
                    </label>
                </div>

                <label class="field">
                    <span>Provider execution</span>
                    <select id="provider" name="provider">
                        <option value="">Automatic fallback</option>
                    </select>
                    <small id="providerHelp">Automatic mode stops after the first successful provider.</small>
                </label>

                <div class="mode-grid">
                    <div class="mode-card">
                        <label class="toggle">
                            <input id="demoMode" name="demo" type="checkbox" value="1">
                            <span class="toggle-ui"></span>
                            <span><strong>Demo mode</strong><small>Test the UI without contacting third-party providers.</small></span>
                        </label>
                    </div>

                    <div class="mode-card">
                        <label class="toggle">
                            <input id="bypassCache" name="bypass_cache" type="checkbox" value="1">
                            <span class="toggle-ui"></span>
                            <span><strong>Bypass cache</strong><small>Skip cache read and write for this request only.</small></span>
                        </label>
                    </div>
                </div>

                <div id="formMessage" class="form-message hidden" role="alert"></div>
                <button id="submitButton" class="button button-primary" type="submit">
                    <span class="button-label">Check account</span>
                    <span class="spinner" aria-hidden="true"></span>
                </button>
                <div id="progress" class="progress hidden" aria-hidden="true"><span></span></div>
            </form>
        </article>

        <aside class="panel information-panel">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Selected game</p>
                    <h2 id="selectedGameName">Waiting for selection</h2>
                </div>
                <span id="selectedStatus" class="tag">—</span>
            </div>
            <dl class="definition-list">
                <div><dt>Code</dt><dd id="selectedCode">—</dd></div>
                <div><dt>Zone required</dt><dd id="selectedZone">—</dd></div>
                <div><dt>Providers</dt><dd id="selectedProviders">—</dd></div>
                <div><dt>Provider availability</dt><dd id="selectedAvailability">—</dd></div>
                <div><dt>Audit verified</dt><dd id="selectedVerified">—</dd></div>
                <div><dt>Aliases</dt><dd id="selectedAliases">—</dd></div>
                <div><dt>Servers</dt><dd id="selectedServers">—</dd></div>
                <div><dt>Maintenance note</dt><dd id="selectedNotes">—</dd></div>
            </dl>
            <div class="notice">
                <strong>Live lookup note</strong>
                <p>Bundled adapters depend on external top-up validation flows. Availability can vary by region and can change without notice.</p>
            </div>
        </aside>
    </section>

    <section id="resultSection" class="panel result-panel hidden" aria-live="polite">
        <div class="panel-heading result-heading">
            <div>
                <p class="eyebrow">Normalized response</p>
                <h2 id="resultTitle">Lookup result</h2>
            </div>
            <div class="result-actions">
                <span id="resultCode" class="tag">—</span>
                <button id="copyJson" class="button button-ghost" type="button">Copy JSON</button>
            </div>
        </div>

        <div id="resultSummary" class="result-summary"></div>

        <div class="result-grid">
            <div class="result-card featured"><span>Nickname</span><strong id="resultNickname">—</strong></div>
            <div class="result-card"><span>Game</span><strong id="resultGame">—</strong></div>
            <div class="result-card"><span>Player ID</span><strong id="resultPlayerId">—</strong></div>
            <div class="result-card"><span>Zone / Server</span><strong id="resultZone">—</strong></div>
            <div class="result-card"><span>Country / Region</span><strong id="resultCountry">—</strong></div>
            <div class="result-card"><span>Provider</span><strong id="resultProvider">—</strong></div>
            <div class="result-card"><span>Source</span><strong id="resultSource">—</strong></div>
            <div class="result-card"><span>Lookup mode</span><strong id="resultLookupMode">—</strong></div>
            <div class="result-card"><span>Cache policy</span><strong id="resultCachePolicy">—</strong></div>
        </div>

        <div class="result-columns">
            <div>
                <h3>Provider diagnostics</h3>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Provider</th><th>Status</th><th>Nickname</th><th>Source</th><th>HTTP</th><th>Time</th><th>Code</th><th>Message</th><th>Details</th></tr></thead>
                        <tbody id="attemptRows"><tr><td colspan="9">No provider responses available.</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div>
                <div class="code-heading"><h3>Raw JSON</h3><span id="responseTime">—</span></div>
                <pre id="rawJson">{}</pre>
            </div>
        </div>
    </section>

    <section class="panel recent-panel">
        <div class="panel-heading">
            <div><p class="eyebrow">Browser-only history</p><h2>Recent checks</h2></div>
            <button id="clearHistory" class="button button-ghost" type="button">Clear</button>
        </div>
        <div id="recentChecks" class="recent-list"><p class="muted">No checks yet.</p></div>
    </section>
</main>

<footer>
    <p>Built for <a href="https://github.com/refatbd/game-account-lookup" rel="noreferrer">refatbd/game-account-lookup</a>. No provider credentials are exposed in the browser.</p>
</footer>

<script src="assets/app.js" defer></script>
</body>
</html>
