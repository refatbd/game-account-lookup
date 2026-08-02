import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { spawn } from 'node:child_process';

class CdpClient {
  constructor(url) {
    this.url = url;
    this.id = 0;
    this.pending = new Map();
  }

  connect() {
    return new Promise((resolve, reject) => {
      this.ws = new WebSocket(this.url);
      this.ws.addEventListener('open', resolve, { once: true });
      this.ws.addEventListener('error', () => reject(new Error('Could not connect to Chrome page.')), { once: true });
      this.ws.addEventListener('message', event => {
        const message = JSON.parse(event.data);
        if (!message.id || !this.pending.has(message.id)) return;
        const { resolve: done, reject: fail } = this.pending.get(message.id);
        this.pending.delete(message.id);
        if (message.error) fail(new Error(message.error.message));
        else done(message.result);
      });
    });
  }

  send(method, params = {}) {
    return new Promise((resolve, reject) => {
      const id = ++this.id;
      this.pending.set(id, { resolve, reject });
      this.ws.send(JSON.stringify({ id, method, params }));
    });
  }

  close() {
    this.ws?.close();
  }
}

const input = await readInput();
const playerId = String(input.player_id ?? input.playerId ?? '').trim();
const pageUrl = String(input.page_url ?? 'https://www.codashop.com/en-my/free-fire');
const timeoutMs = Math.max(30_000, Number(input.timeout_ms ?? 120_000));
const debugPort = Number(input.debug_port ?? 9223);
const profileDir = path.resolve(
  input.profile_dir ?? path.join(process.cwd(), 'template', 'storage', 'codashop-browser-profile'),
);

if (!/^\d{5,20}$/.test(playerId)) {
  finish({ ok: false, code: 'INVALID_PLAYER', message: 'Free Fire UID must contain 5 to 20 digits.' });
}

const chromePath = findChrome();
if (!chromePath) {
  finish({
    ok: false,
    code: 'PROVIDER_NOT_CONFIGURED',
    message: 'Google Chrome or Microsoft Edge is required for browser-assisted Codashop lookup.',
  });
}

fs.mkdirSync(profileDir, { recursive: true });
const endpoint = await ensureChrome(chromePath, profileDir, debugPort);
let client;

try {
  const target = await findPageTarget(endpoint, pageUrl);
  client = new CdpClient(target.webSocketDebuggerUrl);
  await client.connect();
  await client.send('Page.enable');
  await client.send('Runtime.enable');
  await client.send('DOM.enable');
  await client.send('Accessibility.enable');
  await client.send('Page.navigate', { url: pageUrl });

  await waitFor(client, `document.readyState !== 'loading' && !![...document.querySelectorAll('input')].find(e => e.placeholder === 'Enter User ID')`, 45_000);
  await evaluate(client, `(() => {
    const input = [...document.querySelectorAll('input')].find(e => e.placeholder === 'Enter User ID');
    if (!input) return false;
    const setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
    input.focus();
    setter.call(input, ${JSON.stringify(playerId)});
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
    input.blur();
    return true;
  })()`);

  await clickByText(client, '[role="radio"]', 'Weekly Lite');
  await delay(500);
  const paymentStep = await evaluate(client, `(() => {
    const button = [...document.querySelectorAll('button,[role="button"]')].find(e => (e.innerText || '').trim().match(/^Select Payment$/i) && e.getClientRects().length);
    if (!button) return false;
    button.click();
    return true;
  })()`);
  if (paymentStep) await delay(500);

  await clickByText(client, '[role="radio"]', 'MAE');
  await evaluate(client, `window.scrollTo({ top: document.documentElement.scrollHeight, behavior: 'instant' }); true`);
  await delay(750);
  await clickAccessible(client, 'button', 'Buy now');

  const confirmation = await waitForNickname(client, timeoutMs);
  finish({
    ok: true,
    code: 'SUCCESS',
    message: 'Game account found through Codashop browser validation.',
    nickname: confirmation.nickname,
    player_id: playerId,
    provider: 'codashop_browser',
    meta: {
      page_url: pageUrl,
      browser: path.basename(chromePath),
      interactive_verification: true,
      purchase_submitted: false,
    },
  });
} catch (error) {
  const message = error instanceof Error ? error.message : String(error);
  const screenshotPath = path.join(process.cwd(), 'template', 'storage', 'codashop-browser-debug.png');
  if (client) {
    try {
      const shot = await client.send('Page.captureScreenshot', { format: 'png' });
      if (shot?.data) fs.writeFileSync(screenshotPath, Buffer.from(shot.data, 'base64'));
    } catch {
      // Diagnostics are best effort only.
    }
  }
  finish({
    ok: false,
    code: /timeout|verification|nickname/i.test(message) ? 'PROVIDER_RESTRICTED' : 'NETWORK_ERROR',
    message: /timeout|nickname/i.test(message)
      ? 'Codashop needs browser verification. Complete any challenge in the opened Chrome window, then retry.'
      : 'Browser-assisted Codashop lookup failed.',
    meta: { error: message.slice(0, 1000), page_url: pageUrl, screenshot_path: screenshotPath },
  });
} finally {
  client?.close();
}

async function waitForNickname(cdp, timeout) {
  const deadline = Date.now() + timeout;
  while (Date.now() < deadline) {
    const texts = await evaluate(cdp, `[...document.querySelectorAll('[role="dialog"]')].map(e => e.innerText)`);
    for (const text of Array.isArray(texts) ? texts : []) {
      const match = String(text).match(/Enter Nickname\s*\n\s*([^\n]+)(?:\n|$)/i);
      if (match?.[1]?.trim()) return { nickname: match[1].trim(), dialogText: text };
    }
    await delay(500);
  }
  throw new Error(`Nickname confirmation timeout after ${timeout}ms.`);
}

async function clickByText(cdp, selector, text) {
  await waitFor(cdp, `[...document.querySelectorAll(${JSON.stringify(selector)})].some(e => e.innerText.includes(${JSON.stringify(text)}) && e.getClientRects().length)`, 30_000);
  const clicked = await evaluate(cdp, `(() => {
    const element = [...document.querySelectorAll(${JSON.stringify(selector)})].find(e => e.innerText.includes(${JSON.stringify(text)}) && e.getClientRects().length);
    if (!element) return false;
    element.click();
    return true;
  })()`);
  if (!clicked) throw new Error(`Codashop option was not clickable: ${text}`);
}

async function clickAccessible(cdp, role, name) {
  const frameTree = await cdp.send('Page.getFrameTree');
  const frameIds = collectFrameIds(frameTree.frameTree);
  let node;
  const candidates = [];
  for (const frameId of frameIds) {
    const tree = await cdp.send('Accessibility.getFullAXTree', { frameId });
    candidates.push(...(tree.nodes ?? []).filter(item => /buy/i.test(String(item.name?.value ?? ''))).map(item => ({ role: item.role?.value, name: item.name?.value, ignored: item.ignored })));
    node = tree.nodes?.find(item =>
      item.role?.value === role
      && String(item.name?.value ?? '').trim().toLowerCase() === name.toLowerCase()
      && !item.properties?.some(property => property.name === 'disabled' && property.value?.value === true)
    );
    if (node) break;
  }
  if (!node?.backendDOMNodeId && role === 'button' && name === 'Buy now') {
    const metrics = await cdp.send('Page.getLayoutMetrics');
    const viewport = metrics.cssVisualViewport ?? metrics.visualViewport;
    if (viewport?.clientWidth && viewport?.clientHeight) {
      const x = viewport.clientWidth * 0.758;
      const y = viewport.clientHeight * 0.485;
      await cdp.send('Input.dispatchMouseEvent', { type: 'mousePressed', x, y, button: 'left', clickCount: 1 });
      await cdp.send('Input.dispatchMouseEvent', { type: 'mouseReleased', x, y, button: 'left', clickCount: 1 });
      return;
    }
  }
  if (!node?.backendDOMNodeId) throw new Error(`Accessible ${role} was not found: ${name}; candidates=${JSON.stringify(candidates.slice(0, 30))}`);
  const resolved = await cdp.send('DOM.resolveNode', { backendNodeId: node.backendDOMNodeId });
  const objectId = resolved.object?.objectId;
  if (!objectId) throw new Error(`Accessible ${role} could not be resolved: ${name}`);
  await cdp.send('Runtime.callFunctionOn', {
    objectId,
    functionDeclaration: 'function () { this.click(); return true; }',
    returnByValue: true,
  });
}

function collectFrameIds(tree) {
  if (!tree?.frame?.id) return [];
  return [tree.frame.id, ...(tree.childFrames ?? []).flatMap(collectFrameIds)];
}

async function waitFor(cdp, expression, timeout) {
  const deadline = Date.now() + timeout;
  while (Date.now() < deadline) {
    if (await evaluate(cdp, expression)) return;
    await delay(250);
  }
  throw new Error(`Browser state timeout: ${expression}`);
}

async function evaluate(cdp, expression) {
  const response = await cdp.send('Runtime.evaluate', {
    expression,
    awaitPromise: true,
    returnByValue: true,
  });
  if (response?.exceptionDetails) {
    throw new Error(response.exceptionDetails.exception?.description ?? response.exceptionDetails.text ?? 'Browser evaluation failed.');
  }
  return response?.result?.value;
}

async function ensureChrome(executablePath, userDataDir, port) {
  const endpoint = `http://127.0.0.1:${port}`;
  if (await endpointReady(endpoint)) return endpoint;

  const child = spawn(executablePath, [
    `--remote-debugging-port=${port}`,
    `--user-data-dir=${userDataDir}`,
    '--start-maximized',
    '--no-first-run',
    '--no-default-browser-check',
    'about:blank',
  ], { detached: true, stdio: 'ignore' });
  child.unref();

  const deadline = Date.now() + 20_000;
  while (Date.now() < deadline) {
    if (await endpointReady(endpoint)) return endpoint;
    await delay(250);
  }
  throw new Error('Chrome debugging session did not start within 20 seconds.');
}

async function endpointReady(endpoint) {
  try {
    return (await fetch(`${endpoint}/json/version`)).ok;
  } catch {
    return false;
  }
}

async function findPageTarget(endpoint, url) {
  let targets = await (await fetch(`${endpoint}/json/list`)).json();
  let target = targets.find(item => item.type === 'page' && item.url.includes('codashop.com'));
  if (!target) {
    const response = await fetch(`${endpoint}/json/new?${encodeURIComponent(url)}`, { method: 'PUT' });
    target = await response.json();
  }
  if (!target?.webSocketDebuggerUrl) throw new Error('Chrome did not expose a Codashop page target.');
  return target;
}

function findChrome() {
  const candidates = [
    process.env.GAME_LOOKUP_CHROME_PATH,
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
  ].filter(Boolean);
  return candidates.find(candidate => fs.existsSync(candidate)) ?? null;
}

async function readInput() {
  const chunks = [];
  for await (const chunk of process.stdin) chunks.push(chunk);
  const raw = Buffer.concat(chunks).toString('utf8').trim();
  if (!raw) return {};
  try {
    return JSON.parse(raw);
  } catch {
    finish({ ok: false, code: 'INVALID_REQUEST', message: 'Browser helper input must be valid JSON.' });
  }
}

function delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

function finish(payload) {
  process.stdout.write(`${JSON.stringify(payload)}\n`);
  process.exit(payload.ok ? 0 : 1);
}
