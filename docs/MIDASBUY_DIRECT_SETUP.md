# Midasbuy direct session capture and rotation guide

The `midasbuy` provider creates the official `xMidas` request in PHP and sends
it directly to:

```text
POST https://www.midasbuy.com/interface/getCharac
```

It does not need Chrome, Node.js, Playwright, or a browser process at runtime.
For every Player ID, PHP opens the configured Midasbuy page to establish normal
domain-scoped cookies, builds the plaintext JSON, encrypts it, and sends a fresh
`encrypt_msg`. A challenge that rotates the cookie is retried once. This makes
it suitable for shared hosting as long as PHP has OpenSSL and outbound HTTPS
access.

The automatic PUBG Mobile provider order is:

```text
midasbuy -> gopaygames -> midasbuy_browser
```

If the direct Midasbuy session expires, that provider returns a normal failure
and the remaining providers can continue. It must not cause a fatal error.

## What can change

The direct request currently uses these values:

| Configuration value | Current role | How often it is likely to change | Where to obtain it |
|---|---|---|---|
| `endpoint` | Official character lookup URL | Rarely | Successful `getCharac` request URL |
| `referer` | Midasbuy SDK/page context | Region, language, or site release may change it | Successful request headers |
| `appId` | Identifies PUBG Mobile | Rarely | `appid` in the SDK URL or the frontend request builder |
| `zoneId` | Default PUBG lookup zone | Rarely | Frontend request builder/plaintext before encryption |
| `encryptionKey` | 32-byte AES key, stored as 64 hexadecimal characters | Can rotate with a frontend release/session change | Midasbuy JavaScript that creates `encrypt_msg` |
| `encryptionIv` | 16-byte AES-CBC initialization vector | Can change if Midasbuy changes its encryption implementation | Same encryption function in the frontend JavaScript |
| `ctokenVersion` | Declares the client-token format | Can change with frontend code | `ctoken_ver` in a successful request body |
| `ctoken` | Authorizes/validates the encrypted client request | Can expire or rotate | `ctoken` in a successful request body |
| plaintext schema/order | Defines what is encrypted | Can change with the SDK | JavaScript object immediately before encryption |
| request headers | Supplies origin, referrer, and browser context | May change with site/browser releases | Successful request headers |

The key, IV, token version, token, and plaintext format form one compatible
set. When rotating the session, capture them from the same working Midasbuy
release. Mixing old and new values can produce misleading failures.

## Current encryption format

At the time this integration was captured, Midasbuy used:

- AES-256-CBC
- PKCS#7 padding
- a 32-byte key represented by 64 hexadecimal characters
- a 16-byte IV
- Base64 output for `encrypt_msg`

The plaintext is compact JSON in this exact property order:

```json
{"browserParams":"","appid":"1450015065","zoneid":"1","openid":"PLAYER_ID"}
```

Property names, order, quoting, and the absence of spaces matter when comparing
your PHP ciphertext with a ciphertext captured from the browser.

## 1. Capture a known-working request

Use a real Chrome or Edge session only to refresh configuration values. The
deployed PHP lookup itself does not run a browser.

1. Open the official PUBG Mobile Midasbuy page for the intended country, for
   example:

   ```text
   https://www.midasbuy.com/midasbuy/bd/buy/pubgm
   ```

2. Open Developer Tools with `F12` or `Ctrl+Shift+I`.
3. Select **Network**, enable **Preserve log**, and reload the page.
4. Enter a PUBG Player ID that is already known to be valid.
5. Wait until Midasbuy displays the nickname.
6. In the Network filter, search for:

   ```text
   getCharac
   ```

7. Select the successful request to:

   ```text
   https://www.midasbuy.com/interface/getCharac
   ```

8. Confirm that the response contains the expected nickname. Do not capture a
   request that failed, was blocked, or used an invalid Player ID.

The request body should contain fields similar to:

```json
{
  "encrypt_msg": "BASE64_CIPHERTEXT",
  "ctoken_ver": "1.0.1",
  "ctoken": "TOKEN_VALUE",
  "hostname": "www.midasbuy.com"
}
```

Right-click the successful request and choose **Copy -> Copy as cURL**. Keep
that copy only in a private local text file while updating the configuration.

## 2. Collect the directly visible values

From the successful Network request, record:

- Full request URL -> `endpoint`
- Request header `Referer` -> `referer`
- Request body `ctoken_ver` -> `ctokenVersion`
- Request body `ctoken` -> `ctoken`
- Request body `hostname` -> normally `www.midasbuy.com`
- Request header `User-Agent` if Midasbuy has started checking it

The SDK URL normally exposes `appid` as a query parameter, for example:

```text
...common-sdk?...&appid=1450015065&country=bd&...
```

Do not try to read the Player ID or AES key from `encrypt_msg`; it is encrypted.

## 3. Find the current AES key, IV, and plaintext builder

The AES key is normally part of, or supplied to, the frontend JavaScript that
creates `encrypt_msg`.

1. With Developer Tools open, select **Sources**.
2. Use global search (`Ctrl+Shift+F` on Windows/Linux or `Cmd+Option+F` on
   macOS).
3. Search for these strings one at a time:

   ```text
   encrypt_msg
   ctoken_ver
   browserParams
   AES.encrypt
   1234567890123456
   ```

4. Open the matching Midasbuy JavaScript bundle and use the `{}` **Pretty
   print** button if it is minified.
5. Locate the function that builds the object containing `browserParams`,
   `appid`, `zoneid`, and `openid`, then passes its JSON string to AES.
6. Follow the key and IV variables back to their literal values or decoded
   configuration values.

For the current provider configuration:

- `encryptionKey` must contain exactly 64 hexadecimal characters. PHP converts
  it to 32 raw bytes with `hex2bin()`.
- `encryptionIv` must resolve to exactly 16 bytes.

If the JavaScript supplies a normal 32-character text key instead of a hex
key, do not paste it into the current field unchanged. Convert its raw bytes to
hex first, or update `MidasbuyProvider` to match the new algorithm.

If none of the search terms are found, select the `getCharac` request's
**Initiator** tab and open the script/call stack that created it. Midasbuy may
have renamed or split the encryption helper into another bundle.

## 4. Confirm that the captured set matches

Before updating production, reproduce the captured `encrypt_msg` locally. Put
the captured Player ID, key, and IV into this temporary PHP script:

```php
<?php
$playerId = 'KNOWN_VALID_PLAYER_ID';
$keyHex = '64_HEX_CHARACTER_KEY';
$iv = '16_BYTE_IV_VALUE';

$plaintext = json_encode([
    'browserParams' => '',
    'appid' => '1450015065',
    'zoneid' => '1',
    'openid' => $playerId,
], JSON_UNESCAPED_SLASHES);

$encrypted = openssl_encrypt(
    $plaintext,
    'aes-256-cbc',
    hex2bin($keyHex),
    OPENSSL_RAW_DATA,
    $iv,
);

echo base64_encode($encrypted), PHP_EOL;
```

Run it:

```text
php verify-midasbuy-encryption.php
```

The output must exactly equal the `encrypt_msg` captured for the same Player
ID. Delete the temporary script afterward. If it does not match, verify:

1. The Player ID is identical.
2. The JSON property order and values match the current frontend.
3. The key was decoded as hex rather than used as 64 ASCII bytes.
4. The IV and AES mode are correct.
5. The browser request and JavaScript bundle came from the same page load.

## 5. Update or override the rotating values

This private build keeps the currently captured values in the `MIDASBUY` group
inside:

```text
src/Credentials/BundledCredentialProvider.php
```

This allows direct lookup without extra setup while that session remains valid.
When it rotates, replace the complete `MIDASBUY` group together. Alternatively,
a complete environment group overrides the bundled group without editing source:

```dotenv
GAME_LOOKUP_MIDASBUY_ENCRYPTION_KEY="64_HEX_CHARACTER_KEY"
GAME_LOOKUP_MIDASBUY_ENCRYPTION_IV="16_BYTE_IV_VALUE"
GAME_LOOKUP_MIDASBUY_CTOKEN_VERSION="CAPTURED_CTOKEN_VERSION"
GAME_LOOKUP_MIDASBUY_CTOKEN="CAPTURED_CTOKEN"
GAME_LOOKUP_MIDASBUY_USER_AGENT="your-approved-user-agent"
```

### PowerShell

```powershell
$env:GAME_LOOKUP_MIDASBUY_ENCRYPTION_KEY = '64_HEX_CHARACTER_KEY'
$env:GAME_LOOKUP_MIDASBUY_ENCRYPTION_IV = '16_BYTE_IV_VALUE'
$env:GAME_LOOKUP_MIDASBUY_CTOKEN_VERSION = 'CAPTURED_CTOKEN_VERSION'
$env:GAME_LOOKUP_MIDASBUY_CTOKEN = 'CAPTURED_CTOKEN'
$env:GAME_LOOKUP_MIDASBUY_USER_AGENT = 'your-approved-user-agent'
php -S 127.0.0.1:8765 -t template
```

### Linux/macOS shell

```bash
export GAME_LOOKUP_MIDASBUY_ENCRYPTION_KEY='64_HEX_CHARACTER_KEY'
export GAME_LOOKUP_MIDASBUY_ENCRYPTION_IV='16_BYTE_IV_VALUE'
export GAME_LOOKUP_MIDASBUY_CTOKEN_VERSION='CAPTURED_CTOKEN_VERSION'
export GAME_LOOKUP_MIDASBUY_CTOKEN='CAPTURED_CTOKEN'
export GAME_LOOKUP_MIDASBUY_USER_AGENT='your-approved-user-agent'
php -S 127.0.0.1:8765 -t template
```

For shared hosting, either upload the updated private configuration or add the
same names in the hosting environment/application settings.

Restart the local PHP server or hosting PHP worker so OPcache cannot retain the
old values. Never make a repository containing these live values public.

## 6. Test the refreshed direct provider

In the web tester:

1. Select **PUBG Mobile**.
2. Enter the same known-valid Player ID used during capture.
3. Select **Only midasbuy**.
4. Enable **Bypass cache**.
5. Click **Check account**.

Expected result:

```json
{
  "ok": true,
  "code": "SUCCESS",
  "nickname": "PLAYER NAME",
  "provider": "midasbuy",
  "meta": {
    "direct_http": true,
    "browser_assisted": false,
    "encryption_session_configured": true,
    "payload_generated_for_player": true
  }
}
```

Then test a second known-valid ID. Success for two different IDs confirms that
PHP is generating the payload dynamically and that no per-ID ciphertext is
being replayed.

An ID that does not exist should return `INVALID_PLAYER`. That is an expected
account result, not proof that the encryption session is broken.

## 7. Shared-hosting requirements

The direct provider requires:

- PHP with the OpenSSL extension (`openssl_encrypt`)
- PHP cURL or the project's available HTTPS transport
- outbound HTTPS access to `www.midasbuy.com` on port 443
- a valid CA certificate store

Quick OpenSSL check:

```php
<?php
header('Content-Type: application/json');
echo json_encode([
    'openssl_loaded' => extension_loaded('openssl'),
    'encrypt_available' => function_exists('openssl_encrypt'),
]);
```

Delete this diagnostic file after checking. If OpenSSL is unavailable, the
provider returns `PROVIDER_NOT_CONFIGURED`; it does not throw a fatal error, so
the hosting-safe fallback providers can still run.

After uploading changed configuration files, clear OPcache or restart PHP-FPM
from the hosting panel when available. Also ask the host to allow outbound HTTPS
to `www.midasbuy.com` if requests time out or cannot connect.

## 8. Troubleshooting

| Result | Likely cause | Action |
|---|---|---|
| `encryption_session_configured: false` | Missing/invalid key, IV, token, or OpenSSL | Confirm 64 hex key, 16-byte IV, non-empty token, and PHP OpenSSL |
| Response mentions `ctoken`, token expiry, or returns token-related code | `ctoken` or its version rotated/expired | Capture a new successful request and replace token, version, key, and IV as one set |
| Known-valid ID returns a generic Midasbuy error | Key/IV/plaintext schema no longer matches the frontend | Reinspect the JavaScript encryption function and compare ciphertext locally |
| One ID succeeds but another returns `INVALID_PLAYER` | Second ID is invalid or unavailable in the selected PUBG service | Verify the ID on the official page; do not rotate credentials unnecessarily |
| All valid IDs return `INVALID_PLAYER` | Upstream response schema or encrypted plaintext changed | Inspect the raw Midasbuy response and current frontend builder |
| HTTP `403` or an HTML block page | Origin/referrer/header policy, network reputation, or upstream protection changed | Compare request headers with a successful browser request and try the intended hosting network |
| HTTP timeout/DNS/TLS error | Hosting blocks outbound HTTPS or has a CA/DNS problem | Ask the host to permit `www.midasbuy.com:443` and verify its CA store |
| Works locally but not on shared hosting | Old OPcache, missing OpenSSL, blocked egress, or different source-network policy | Restart PHP, run the boolean diagnostic, and check outbound access |
| `payload_generated_for_player: true` but lookup is restricted | Encryption ran successfully, but the captured Midasbuy session is no longer accepted | Refresh the complete session set |

## 9. Rotation checklist

When direct lookup stops working for multiple known-valid IDs:

1. Confirm that GoPay/fallback behavior is still non-fatal.
2. Open the official PUBG Midasbuy page in a normal browser.
3. Use a known-valid Player ID and capture a successful `getCharac` request.
4. Record endpoint, referrer, `ctoken_ver`, `ctoken`, and hostname.
5. Inspect the initiating JavaScript for the current key, IV, algorithm, and
   plaintext schema.
6. Reproduce the captured `encrypt_msg` with the temporary PHP verification
   script.
7. Update the bundled `MIDASBUY` credential group or all four environment
   overrides.
8. Run `composer quality` if provider code or stable registry metadata changed.
9. Restart PHP/clear OPcache.
10. Test **Only midasbuy** with cache bypass using at least two valid IDs.
11. Remove captured tokens, cURL commands, screenshots, and temporary scripts
    from shared locations and shell history.

Never expose a live `ctoken` or encryption key in frontend JavaScript, API
responses, public repositories, screenshots, or debug logs. This build is
intended for a private repository only.
