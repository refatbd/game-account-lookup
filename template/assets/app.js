'use strict';

const state = {
    games: [],
    visibleGames: [],
    response: null,
};

const el = (id) => document.getElementById(id);
const form = el('lookupForm');
const gameSelect = el('game');
const playerId = el('playerId');
const zoneId = el('zoneId');
const zoneField = el('zoneField');
const providerSelect = el('provider');
const demoMode = el('demoMode');
const bypassCache = el('bypassCache');
const submitButton = el('submitButton');
const progress = el('progress');
const formMessage = el('formMessage');
const gameAvailability = el('gameAvailability');

const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

function setApiStatus(ok, text) {
    const node = el('apiStatus');
    node.className = `status-pill ${ok ? 'status-ok' : 'status-error'}`;
    node.innerHTML = `<span class="dot"></span>${escapeHtml(text)}`;
}

async function fetchJson(url, options = {}) {
    const response = await fetch(url, { ...options, headers: { Accept: 'application/json', ...(options.headers || {}) } });
    const data = await response.json().catch(() => ({ ok: false, code: 'NON_JSON', message: 'The server returned a non-JSON response.' }));
    return { response, data };
}

async function boot() {
    demoMode.checked = document.body.dataset.demoDefault === '1';

    try {
        const health = await fetchJson('api/health.php');
        if (!health.response.ok || !health.data.ok) throw new Error(health.data.message || 'Health check failed.');

        const games = await fetchJson('api/games.php');
        if (!games.response.ok || !games.data.ok) throw new Error(games.data.message || 'Could not load games.');

        state.games = games.data.games || [];
        state.visibleGames = state.games;
        renderGameOptions();
        el('totalGames').textContent = health.data.games.total;
        el('activeGames').textContent = health.data.games.active;
        setApiStatus(true, health.data.live_ready ? `API ready · PHP ${health.data.php_version}` : 'Demo ready · cURL missing');
        gameSelect.value = state.games.some((game) => game.code === 'freefire') ? 'freefire' : (state.games[0]?.code || '');
        syncSelectedGame();
    } catch (error) {
        setApiStatus(false, 'API unavailable');
        showFormMessage(error.message || 'Failed to initialize the template.');
    }

    renderHistory();
}

function renderGameOptions() {
    const selected = gameSelect.value;
    gameSelect.innerHTML = '<option value="">Choose a supported game</option>' + state.visibleGames.map((game) => {
        const suffix = game.status === 'active' ? '' : ` · ${game.status}`;
        return `<option value="${escapeHtml(game.code)}">${escapeHtml(game.label)}${escapeHtml(suffix)}</option>`;
    }).join('');

    if (state.visibleGames.some((game) => game.code === selected)) gameSelect.value = selected;
}

function selectedGame() {
    return state.games.find((game) => game.code === gameSelect.value) || null;
}

function liveLookupAvailable(game = selectedGame()) {
    return Boolean(game && game.status === 'active' && Array.isArray(game.providers) && game.providers.length > 0);
}

function syncSubmitAvailability() {
    const game = selectedGame();
    const allowed = Boolean(game) && (demoMode.checked || liveLookupAvailable(game));
    submitButton.disabled = submitButton.classList.contains('loading') || !allowed;
}

function syncProviderHelp() {
    const help = el('providerHelp');
    if (providerSelect.disabled) {
        help.textContent = 'No enabled live provider is available. Enable Demo mode to test this definition.';
    } else if (providerSelect.value === '__all__') {
        help.textContent = 'Every configured provider is called and each normalized response is shown, even after one succeeds.';
    } else if (providerSelect.value) {
        help.textContent = `Only ${providerSelect.value} will be called. No fallback provider will run.`;
    } else {
        help.textContent = 'Automatic fallback follows registry order and stops after the first successful provider.';
    }
}

function syncSelectedGame() {
    const game = selectedGame();
    if (!game) {
        zoneField.classList.add('hidden');
        el('selectedGameName').textContent = 'Waiting for selection';
        ['selectedCode', 'selectedZone', 'selectedProviders', 'selectedAvailability', 'selectedVerified', 'selectedAliases', 'selectedServers', 'selectedNotes'].forEach((id) => { el(id).textContent = '—'; });
        const status = el('selectedStatus');
        status.textContent = '—';
        status.className = 'tag';
        providerSelect.innerHTML = '<option value="">Automatic fallback</option>';
        providerSelect.disabled = false;
        gameAvailability.classList.add('hidden');
        syncProviderHelp();
        syncSubmitAvailability();
        return;
    }

    const liveAvailable = liveLookupAvailable(game);
    el('selectedGameName').textContent = game.label;
    el('selectedCode').textContent = game.code;
    el('selectedZone').textContent = game.requires_zone ? 'Yes' : 'No';
    el('selectedProviders').textContent = game.providers.length ? game.providers.join(', ') : 'No enabled provider';
    const availability = game.provider_availability || {};
    el('selectedAvailability').textContent = Object.keys(availability).length
        ? Object.entries(availability).map(([provider, info]) => `${provider}: ${info.status || 'unknown'}`).join(' · ')
        : 'No provider audit data';
    el('selectedVerified').textContent = game.provider_audit_verified_at || 'Not audited';
    el('selectedAliases').textContent = game.aliases.length ? game.aliases.join(', ') : 'None';
    el('selectedServers').textContent = game.servers.length ? game.servers.join(', ') : 'Free-form / not listed';
    el('selectedNotes').textContent = game.notes || 'None';
    const status = el('selectedStatus');
    status.textContent = game.status;
    status.className = `tag ${game.status === 'active' ? 'status-active' : 'status-maintenance'}`;
    el('gameHelp').textContent = `${game.label} uses ${game.providers.length ? game.providers.join(' → ') : 'no enabled provider'}${game.requires_zone ? ' and requires a zone/server.' : '.'}`;

    zoneField.classList.toggle('hidden', !game.requires_zone);
    zoneId.required = Boolean(game.requires_zone);
    el('zoneHelp').textContent = game.servers.length ? `Known server values: ${game.servers.join(', ')}` : 'Enter the zone/server value required by the provider.';

    const previous = providerSelect.value;
    if (game.providers.length) {
        providerSelect.innerHTML = [
            '<option value="">Automatic fallback</option>',
            ...(game.providers.length > 1 ? ['<option value="__all__">All providers — show every response</option>'] : []),
            ...game.providers.map((provider) => `<option value="${escapeHtml(provider)}">Only ${escapeHtml(provider)}</option>`),
        ].join('');
        providerSelect.disabled = false;
        providerSelect.value = [...providerSelect.options].some((option) => option.value === previous) ? previous : '';
    } else {
        providerSelect.innerHTML = '<option value="">No live provider available</option>';
        providerSelect.disabled = true;
    }

    if (liveAvailable) {
        gameAvailability.classList.add('hidden');
        gameAvailability.textContent = '';
    } else {
        const notes = game.notes ? ` ${game.notes}` : '';
        gameAvailability.innerHTML = `<strong>Live lookup unavailable:</strong> ${escapeHtml(game.status)}.${escapeHtml(notes)} Enable Demo mode to test the form without contacting a provider.`;
        gameAvailability.classList.remove('hidden');
    }

    syncProviderHelp();
    syncSubmitAvailability();
}

function setLoading(loading) {
    submitButton.classList.toggle('loading', loading);
    const allProviders = providerSelect.value === '__all__';
    submitButton.querySelector('.button-label').textContent = loading
        ? (allProviders ? 'Checking all providers…' : 'Checking account…')
        : 'Check account';
    progress.classList.toggle('hidden', !loading);
    syncSubmitAvailability();
}

function showFormMessage(message) {
    formMessage.textContent = message;
    formMessage.classList.remove('hidden');
}

function clearFormMessage() {
    formMessage.textContent = '';
    formMessage.classList.add('hidden');
}

function lookupModeLabel(data) {
    if (data.lookup_mode === 'all') return 'All providers';
    if (data.lookup_mode === 'single') return 'Single provider';
    if (data.lookup_mode === 'unavailable') return 'Unavailable';
    return 'Automatic fallback';
}

function cachePolicyLabel(data) {
    if (data.cache_bypassed) return 'Bypassed for request';
    if (data.lookup_mode === 'all' && data.summary) {
        return `${data.summary.cached || 0}/${data.summary.providers_checked || 0} cached`;
    }
    return data.cached ? 'Cache hit' : 'Cache enabled';
}

function diagnosticDetails(meta = {}) {
    const parts = [];
    if (meta.codashop_profile) parts.push(`Profile: ${meta.codashop_profile}`);
    if (meta.profiles_checked) parts.push(`Profiles checked: ${meta.profiles_checked}`);
    if (Array.isArray(meta.profile_attempts) && meta.profile_attempts.length) {
        const route = meta.profile_attempts.map((attempt) => `${attempt.profile || 'unknown'}:${attempt.code || (attempt.ok ? 'SUCCESS' : 'FAILED')}`);
        parts.push(`Route: ${route.join(' → ')}`);
    }
    if (meta.upstream_host) parts.push(`Host: ${meta.upstream_host}`);
    if (meta.ray_id) parts.push(`Ray: ${meta.ray_id}`);
    if (meta.retry_after) parts.push(`Retry: ${meta.retry_after}s`);
    if (meta.transport_error) parts.push(meta.transport_error);
    if (meta.response_preview) parts.push(meta.response_preview);
    return parts.join(' · ');
}

function providerDisplayName(provider, meta = {}) {
    if (meta.codashop_profile) return `${provider || 'codashop'} (${meta.codashop_profile})`;
    return provider || '—';
}

function providerRows(data) {
    if (Array.isArray(data.provider_results)) {
        return data.provider_results.map((result) => {
            const meta = result.meta || {};
            return {
                provider: providerDisplayName(result.requested_provider || result.provider, meta),
                ok: Boolean(result.ok),
                nickname: result.nickname,
                source: data.mode === 'demo' ? 'Demo' : (result.cached ? 'Cache' : 'Live'),
                httpStatus: meta.http_status,
                duration: meta.duration_ms,
                code: result.code,
                message: result.message,
                details: diagnosticDetails(meta),
            };
        });
    }

    return (Array.isArray(data.attempts) ? data.attempts : []).map((attempt) => {
        const meta = attempt.meta || {};
        return {
            provider: providerDisplayName(attempt.provider, meta),
            ok: Boolean(attempt.ok),
            nickname: attempt.nickname || (attempt.ok && attempt.provider === data.provider ? data.nickname : null),
            source: data.mode === 'demo' ? 'Demo' : (attempt.cached || data.cached ? 'Cache' : 'Live'),
            httpStatus: meta.http_status,
            duration: meta.duration_ms,
            code: attempt.code,
            message: attempt.message,
            details: diagnosticDetails(meta),
        };
    });
}

function renderResult(data, elapsed) {
    state.response = data;
    const success = Boolean(data.ok);
    const allProviders = data.lookup_mode === 'all';
    const maintenance = data.code === 'MAINTENANCE_REQUIRED';
    el('resultSection').classList.remove('hidden');
    el('resultTitle').textContent = maintenance ? 'Live lookup unavailable' : (allProviders ? 'Provider comparison complete' : (success ? 'Account resolved' : 'Lookup failed'));
    el('resultCode').textContent = data.code || 'UNKNOWN';
    const summary = el('resultSummary');
    summary.className = `result-summary ${success ? 'success' : 'failure'}`;
    summary.textContent = data.message || (success ? 'Lookup completed.' : 'Lookup failed.');

    el('resultNickname').textContent = allProviders
        ? (data.summary ? `${data.summary.succeeded} successful response${data.summary.succeeded === 1 ? '' : 's'}` : (data.nickname || 'Not available'))
        : (data.nickname || 'Not available');
    el('resultGame').textContent = data.game || gameSelect.value || '—';
    el('resultPlayerId').textContent = data.player_id || playerId.value || '—';
    el('resultZone').textContent = data.zone_id || data.server || 'Not required';
    el('resultCountry').textContent = data.country || data.meta?.country || 'Not available';
    el('resultProvider').textContent = allProviders ? 'All configured providers' : (data.provider || '—');
    el('resultSource').textContent = maintenance ? 'Registry maintenance' : (data.mode === 'demo' ? 'Demo generator' : (allProviders ? 'Provider comparison' : (data.cached ? 'Local cache' : 'Live provider')));
    el('resultLookupMode').textContent = lookupModeLabel(data);
    el('resultCachePolicy').textContent = cachePolicyLabel(data);
    el('responseTime').textContent = `${elapsed} ms`;
    el('rawJson').textContent = JSON.stringify(data, null, 2);

    const rows = providerRows(data);
    el('attemptRows').innerHTML = rows.length ? rows.map((row) => `
        <tr>
            <td>${escapeHtml(row.provider || '—')}</td>
            <td>${row.ok ? 'Success' : 'Failed'}</td>
            <td>${escapeHtml(row.nickname || '—')}</td>
            <td>${escapeHtml(row.source || '—')}</td>
            <td>${escapeHtml(row.httpStatus ?? '—')}</td>
            <td>${row.duration !== null && row.duration !== undefined ? `${escapeHtml(row.duration)} ms` : '—'}</td>
            <td>${escapeHtml(row.code || '—')}</td>
            <td>${escapeHtml(row.message || '—')}</td>
            <td><span class="diagnostic-detail">${escapeHtml(row.details || '—')}</span></td>
        </tr>`).join('') : '<tr><td colspan="9">No provider responses were returned.</td></tr>';

    saveHistory(data);
    el('resultSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function historyItems() {
    try { return JSON.parse(localStorage.getItem('gameLookupHistory') || '[]'); } catch { return []; }
}

function saveHistory(data) {
    const game = selectedGame();
    const nickname = data.lookup_mode === 'all' && data.summary
        ? `${data.summary.succeeded}/${data.summary.providers_checked} providers succeeded`
        : (data.nickname || data.code || 'Failed');
    const item = {
        game: game?.label || data.game || gameSelect.value,
        player: data.player_id || playerId.value,
        nickname,
        ok: Boolean(data.ok),
        at: new Date().toISOString(),
    };
    const items = [item, ...historyItems().filter((old) => !(old.game === item.game && old.player === item.player))].slice(0, 8);
    localStorage.setItem('gameLookupHistory', JSON.stringify(items));
    renderHistory();
}

function renderHistory() {
    const items = historyItems();
    el('recentChecks').innerHTML = items.length ? items.map((item) => `
        <div class="recent-item">
            <strong>${escapeHtml(item.game)}</strong>
            <span>${escapeHtml(item.player)}</span>
            <span>${escapeHtml(item.nickname)}</span>
            <small>${new Date(item.at).toLocaleString()}</small>
        </div>`).join('') : '<p class="muted">No checks yet.</p>';
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearFormMessage();
    const game = selectedGame();

    if (!game) return showFormMessage('Choose a game.');
    if (!playerId.value.trim()) return showFormMessage('Enter a player ID or UID.');
    if (game.requires_zone && !zoneId.value.trim()) return showFormMessage('This game requires a zone or server ID.');
    if (!demoMode.checked && !liveLookupAvailable(game)) {
        return showFormMessage(`Live lookup is unavailable for ${game.label} (${game.status}). Enable Demo mode to test the UI.`);
    }

    setLoading(true);
    const started = performance.now();
    try {
        const body = new URLSearchParams({
            game: game.code,
            player_id: playerId.value.trim(),
            zone_id: zoneId.value.trim(),
            provider: providerSelect.value,
            demo: demoMode.checked ? '1' : '0',
            bypass_cache: bypassCache.checked ? '1' : '0',
        });
        const { response, data } = await fetchJson('api/check.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body,
        });
        renderResult(data, Math.round(performance.now() - started));
        if (!response.ok && !data.message) showFormMessage(`Request failed with HTTP ${response.status}.`);
    } catch (error) {
        showFormMessage(error.message || 'The lookup request failed.');
    } finally {
        setLoading(false);
    }
});

gameSelect.addEventListener('change', syncSelectedGame);
providerSelect.addEventListener('change', syncProviderHelp);
demoMode.addEventListener('change', () => {
    clearFormMessage();
    syncSubmitAvailability();
});
el('gameSearch').addEventListener('input', (event) => {
    const query = event.target.value.trim().toLowerCase();
    state.visibleGames = query ? state.games.filter((game) => [game.label, game.code, ...(game.aliases || [])].join(' ').toLowerCase().includes(query)) : state.games;
    renderGameOptions();
    syncSelectedGame();
});

el('loadSample').addEventListener('click', () => {
    gameSelect.value = 'freefire';
    playerId.value = '4422076728';
    zoneId.value = '';
    syncSelectedGame();
    playerId.focus();
});

el('copyJson').addEventListener('click', async () => {
    if (!state.response) return;
    await navigator.clipboard.writeText(JSON.stringify(state.response, null, 2));
    const button = el('copyJson');
    const original = button.textContent;
    button.textContent = 'Copied';
    setTimeout(() => { button.textContent = original; }, 1200);
});

el('clearHistory').addEventListener('click', () => {
    localStorage.removeItem('gameLookupHistory');
    renderHistory();
});

boot();
