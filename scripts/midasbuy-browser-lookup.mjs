import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { spawn } from 'node:child_process';

class CdpClient {
  constructor(url) { this.url = url; this.id = 0; this.pending = new Map(); this.listeners = new Map(); }
  connect() {
    return new Promise((resolve, reject) => {
      this.ws = new WebSocket(this.url);
      this.ws.addEventListener('open', resolve, { once: true });
      this.ws.addEventListener('error', () => reject(new Error('Could not connect to Chrome page.')), { once: true });
      this.ws.addEventListener('message', event => {
        const message = JSON.parse(event.data);
        if (!message.id) {
          for (const handler of this.listeners.get(message.method) ?? []) handler(message.params ?? {});
          return;
        }
        if (!this.pending.has(message.id)) return;
        const handler = this.pending.get(message.id);
        this.pending.delete(message.id);
        message.error ? handler.reject(new Error(message.error.message)) : handler.resolve(message.result);
      });
    });
  }
  on(method, handler) {
    const handlers = this.listeners.get(method) ?? [];
    handlers.push(handler);
    this.listeners.set(method, handlers);
  }
  send(method, params = {}) {
    return new Promise((resolve, reject) => {
      const id = ++this.id;
      this.pending.set(id, { resolve, reject });
      this.ws.send(JSON.stringify({ id, method, params }));
    });
  }
  close() { this.ws?.close(); }
}

const input = await readInput();
const playerId = String(input.player_id ?? input.playerId ?? '').trim();
const pageUrl = String(input.page_url ?? 'https://www.midasbuy.com/midasbuy/bd/buy/pubgm');
const timeoutMs = Math.max(30_000, Number(input.timeout_ms ?? 90_000));
const debugPort = Number(input.debug_port ?? 9224);
const profileDir = path.resolve(input.profile_dir ?? path.join(process.cwd(), 'template', 'storage', 'midasbuy-browser-profile'));

if (!/^\d{5,20}$/.test(playerId)) finish({ ok: false, code: 'INVALID_PLAYER', message: 'PUBG Mobile Player ID must contain 5 to 20 digits.' });
const chromePath = findChrome();
if (!chromePath) finish({ ok: false, code: 'PROVIDER_NOT_CONFIGURED', message: 'Google Chrome or Microsoft Edge is required for browser-assisted Midasbuy lookup.' });

fs.mkdirSync(profileDir, { recursive: true });
const endpoint = await ensureChrome(chromePath, profileDir, debugPort);
let client;

try {
  const target = await findPageTarget(endpoint, pageUrl);
  client = new CdpClient(target.webSocketDebuggerUrl);
  await client.connect();
  let characterResponseId = null;
  client.on('Network.responseReceived', event => {
    const url = String(event.response?.url ?? '');
    if (url.includes('/interface/getCharac')) characterResponseId = event.requestId;
  });
  await client.send('Network.enable');
  await client.send('Page.enable');
  await client.send('Runtime.enable');
  await client.send('DOM.enable');
  await client.send('Page.navigate', { url: pageUrl });
  await waitFor(client, `document.readyState !== 'loading'`, 30_000);
  await waitFor(client, `[...document.querySelectorAll('button,[role="button"],div,span')].some(el => ((el.innerText||'').trim()==='Enter Your Player ID Now' || /^\\(\\d+\\)$/.test((el.textContent||'').trim())) && el.getClientRects().length)`, 30_000);
  let nickname = await readNickname(client, playerId);
  let characterMeta = {};
  if (!nickname) {
    await openPlayerForm(client);
    const frameId = await waitForFrame(client, 'common-sdk', 30_000);
    const world = await client.send('Page.createIsolatedWorld', { frameId, worldName: `midasbuy-lookup-${Date.now()}` });
    const contextId = world.executionContextId;
    await waitFor(client, `!!document.querySelector('input[placeholder="Enter Player ID"]')`, 30_000, contextId);
    await evaluate(client, `(() => { const input=document.querySelector('input[placeholder="Enter Player ID"]'); const setter=Object.getOwnPropertyDescriptor(HTMLInputElement.prototype,'value').set; setter.call(input,${JSON.stringify(playerId)}); input.dispatchEvent(new Event('input',{bubbles:true})); input.dispatchEvent(new Event('change',{bubbles:true})); return true; })()`, contextId);
    const clicked = await evaluate(client, `(() => { const nodes=[...document.querySelectorAll('button,[role="button"],div,span')].reverse(); const button=nodes.find(el => (el.innerText||'').trim()==='OK' && el.getClientRects().length); if(!button)return false; button.click(); return true; })()`, contextId);
    if (!clicked) throw new Error('Midasbuy confirmation button was not found.');
    const character = await waitForCharacterResponse(client, () => characterResponseId, Math.min(timeoutMs, 30_000));
    if (character?.ret === 0 && character.info) {
      nickname = decodeName(character.info.charac_name);
      characterMeta = {
        game_openid: String(character.info.openid ?? '') || undefined,
        zone_id: String(character.info.zoneid ?? '') || undefined,
        country: String(character.info.active_country || character.info.register_country || '').toUpperCase() || undefined,
        is_ban: character.info.is_ban === true,
      };
    } else if (character && character.ret !== 0) {
      finish({ ok: false, code: 'INVALID_PLAYER', message: String(character.msg || 'PUBG Mobile account was not found.'), provider: 'midasbuy_browser', meta: { page_url: pageUrl, upstream_ret: character.ret } });
    }
    if (!nickname) nickname = await waitForNickname(client, playerId, timeoutMs);
  }
  finish({
    ok: true,
    code: 'SUCCESS',
    message: 'PUBG Mobile account found through Midasbuy browser validation.',
    nickname,
    player_id: playerId,
    provider: 'midasbuy_browser',
    country: characterMeta.country ?? null,
    meta: { page_url: pageUrl, browser: path.basename(chromePath), interactive_verification: true, purchase_submitted: false, ...characterMeta },
  });
} catch (error) {
  const message = error instanceof Error ? error.message : String(error);
  const screenshotPath = path.join(process.cwd(), 'template', 'storage', 'midasbuy-browser-debug.png');
  if (client) {
    try {
      const shot = await client.send('Page.captureScreenshot', { format: 'png' });
      if (shot?.data) fs.writeFileSync(screenshotPath, Buffer.from(shot.data, 'base64'));
    } catch {}
  }
  finish({
    ok: false,
    code: /timeout|verification|nickname/i.test(message) ? 'PROVIDER_RESTRICTED' : 'NETWORK_ERROR',
    message: /timeout|nickname/i.test(message) ? 'Midasbuy could not complete browser validation. Complete any challenge in the opened browser, then retry.' : 'Browser-assisted Midasbuy lookup failed.',
    meta: { error: message.slice(0, 1000), page_url: pageUrl, screenshot_path: screenshotPath },
  });
} finally { client?.close(); }

async function waitForNickname(cdp, uid, timeout) {
  const deadline = Date.now() + timeout;
  while (Date.now() < deadline) {
    const nickname = await readNickname(cdp, uid);
    if (typeof nickname === 'string' && nickname.trim()) return nickname.trim();
    await delay(300);
  }
  throw new Error(`Nickname confirmation timeout after ${timeout}ms.`);
}
async function waitForCharacterResponse(cdp, getRequestId, timeout) {
  const deadline = Date.now() + timeout;
  while (Date.now() < deadline) {
    const requestId = getRequestId();
    if (requestId) {
      try {
        const payload = await cdp.send('Network.getResponseBody', { requestId });
        return JSON.parse(String(payload.body ?? ''));
      } catch {}
    }
    await delay(150);
  }
  return null;
}
function decodeName(value) {
  const name = String(value ?? '').trim();
  try { return decodeURIComponent(name); } catch { return name; }
}

async function readNickname(cdp, uid) {
  return await evaluate(cdp, `(() => { const id=[...document.querySelectorAll('span')].find(el => (el.textContent||'').trim()===${JSON.stringify(`(${uid})`)}); if(!id)return ''; const spans=id.parentElement ? [...id.parentElement.querySelectorAll(':scope > span')] : []; return (spans.find(el=>el!==id)?.textContent||'').trim(); })()`);
}
async function openPlayerForm(cdp) {
  const clicked = await evaluate(cdp, `(() => {
    const nodes=[...document.querySelectorAll('button,[role="button"],div,span')].reverse();
    let el=nodes.find(el => (el.innerText||'').trim()==='Enter Your Player ID Now' && el.getClientRects().length);
    if(!el){ const id=[...document.querySelectorAll('span')].find(el => /^\\(\\d+\\)$/.test((el.textContent||'').trim()) && el.getClientRects().length); el=id?.parentElement; }
    if(!el)return false; el.click(); return true;
  })()`);
  if (!clicked) throw new Error('Midasbuy Player ID entry control was not found.');
}
async function waitForFrame(cdp, urlPart, timeout) {
  const deadline = Date.now() + timeout;
  while (Date.now() < deadline) {
    const tree = await cdp.send('Page.getFrameTree');
    const id = findFrame(tree.frameTree, urlPart);
    if (id) return id;
    await delay(250);
  }
  throw new Error(`Midasbuy player frame timeout after ${timeout}ms.`);
}
function findFrame(tree, urlPart) {
  if (tree?.frame?.url?.includes(urlPart)) return tree.frame.id;
  for (const child of tree?.childFrames ?? []) { const found = findFrame(child, urlPart); if (found) return found; }
  return null;
}
async function waitFor(cdp, expression, timeout, contextId) {
  const deadline = Date.now() + timeout;
  while (Date.now() < deadline) { if (await evaluate(cdp, expression, contextId)) return; await delay(250); }
  throw new Error(`Browser state timeout: ${expression}`);
}
async function evaluate(cdp, expression, contextId) {
  const response = await cdp.send('Runtime.evaluate', { expression, ...(contextId ? { contextId } : {}), awaitPromise: true, returnByValue: true });
  if (response?.exceptionDetails) throw new Error(response.exceptionDetails.exception?.description ?? response.exceptionDetails.text ?? 'Browser evaluation failed.');
  return response?.result?.value;
}
async function ensureChrome(executablePath, userDataDir, port) {
  const endpoint = `http://127.0.0.1:${port}`;
  if (await endpointReady(endpoint)) return endpoint;
  spawn(executablePath, [`--remote-debugging-port=${port}`, `--user-data-dir=${userDataDir}`, '--start-maximized', '--no-first-run', '--no-default-browser-check', 'about:blank'], { detached: true, stdio: 'ignore' }).unref();
  const deadline = Date.now() + 20_000;
  while (Date.now() < deadline) { if (await endpointReady(endpoint)) return endpoint; await delay(250); }
  throw new Error('Chrome debugging session did not start within 20 seconds.');
}
async function endpointReady(endpoint) { try { return (await fetch(`${endpoint}/json/version`)).ok; } catch { return false; } }
async function findPageTarget(endpoint, url) {
  const targets = await (await fetch(`${endpoint}/json/list`)).json();
  let target = targets.find(item => item.type === 'page' && item.url.includes('midasbuy.com'));
  if (!target) target = await (await fetch(`${endpoint}/json/new?${encodeURIComponent(url)}`, { method: 'PUT' })).json();
  if (!target?.webSocketDebuggerUrl) throw new Error('Chrome did not expose a Midasbuy page target.');
  return target;
}
function findChrome() {
  const candidates = [process.env.GAME_LOOKUP_CHROME_PATH, 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', 'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe', 'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe', 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe'].filter(Boolean);
  return candidates.find(candidate => fs.existsSync(candidate)) ?? null;
}
async function readInput() {
  const chunks = []; for await (const chunk of process.stdin) chunks.push(chunk);
  const raw = Buffer.concat(chunks).toString('utf8').trim(); if (!raw) return {};
  try { return JSON.parse(raw); } catch { finish({ ok: false, code: 'INVALID_REQUEST', message: 'Browser helper input must be valid JSON.' }); }
}
function delay(ms) { return new Promise(resolve => setTimeout(resolve, ms)); }
function finish(payload) { process.stdout.write(`${JSON.stringify(payload)}\n`); process.exit(payload.ok ? 0 : 1); }
