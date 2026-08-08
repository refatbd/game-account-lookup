<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Providers;

use Refatbd\GameAccountLookup\Credentials\BundledCredentialProvider;
use Refatbd\GameAccountLookup\Credentials\ChainCredentialProvider;
use Refatbd\GameAccountLookup\Credentials\CredentialProviderInterface;
use Refatbd\GameAccountLookup\Credentials\EnvironmentCredentialProvider;
use Refatbd\GameAccountLookup\Contracts\HttpClientInterface;
use Refatbd\GameAccountLookup\Contracts\ProviderInterface;
use Refatbd\GameAccountLookup\DTO\LookupResult;
use Refatbd\GameAccountLookup\Http\HttpResponse;
use Refatbd\GameAccountLookup\ResultCode;
use Refatbd\GameAccountLookup\Support\Arr;
use Refatbd\GameAccountLookup\Support\Normalizer;
use Refatbd\GameAccountLookup\Support\ProviderDiagnostics;
use Refatbd\GameAccountLookup\Support\UserAgent;

/** Direct HTTP implementation of the official Midasbuy xMidas request. */
final class MidasbuyProvider implements ProviderInterface
{
    private const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36';

    private readonly CredentialProviderInterface $credentials;

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly bool $debug = false,
        ?CredentialProviderInterface $credentials = null,
    ) {
        $this->credentials = $credentials ?? new ChainCredentialProvider([
            new EnvironmentCredentialProvider(),
            new BundledCredentialProvider(),
        ]);
    }

    public function key(): string
    {
        return 'midasbuy';
    }

    public function supports(array $game): bool
    {
        $config = $game['providers'][$this->key()] ?? null;

        return is_array($config) && ($config['enabled'] ?? true) === true;
    }

    public function lookup(array $game, string $playerId, ?string $zoneId = null): LookupResult
    {
        $config = (array) ($game['providers'][$this->key()] ?? []);
        $endpoint = (string) ($config['endpoint'] ?? 'https://www.midasbuy.com/interface/getCharac');
        $referer = (string) ($config['referer'] ?? 'https://www.midasbuy.com/common-sdk?id=playerid_enter&appid=1450015065&country=bd&removeIframeBeforeLoad=true&from=self.midasbuy_saas&lang=en&shopcode=midasbuy');
        $appId = (string) ($config['appId'] ?? '1450015065');
        $requestZoneId = (string) ($config['zoneId'] ?? '1');
        $userAgent = UserAgent::resolve(
            'GAME_LOOKUP_MIDASBUY_USER_AGENT',
            self::DEFAULT_USER_AGENT,
            $config['userAgent'] ?? null,
        );
        $credential = $this->credentials->forProvider($this->key());
        $keyHex = trim((string) ($credential?->value('encryptionKey') ?? ''));
        $iv = (string) ($credential?->value('encryptionIv') ?? '');
        $ctokenVersion = trim((string) ($credential?->value('ctokenVersion') ?? ''));
        $ctoken = trim((string) ($credential?->value('ctoken') ?? ''));

        $key = ctype_xdigit($keyHex) ? hex2bin($keyHex) : false;
        if (!function_exists('openssl_encrypt') || !is_string($key) || strlen($key) !== 32 || strlen($iv) !== 16 || $ctoken === '') {
            return LookupResult::failure(
                ResultCode::PROVIDER_NOT_CONFIGURED,
                'Direct Midasbuy encryption session is not configured correctly.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
                ['direct_http' => true, 'browser_assisted' => false, 'encryption_session_configured' => false],
            );
        }

        $plaintext = json_encode([
            'browserParams' => '',
            'appid' => $appId,
            'zoneid' => $requestZoneId,
            'openid' => $playerId,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encrypted = is_string($plaintext)
            ? openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv)
            : false;

        if (!is_string($encrypted)) {
            return LookupResult::failure(ResultCode::INVALID_RESPONSE, 'Could not encrypt the direct Midasbuy request.', $game['code'] ?? null, $playerId, $zoneId, $this->key());
        }

        $payload = [
            'encrypt_msg' => base64_encode($encrypted),
            'ctoken_ver' => $ctokenVersion,
            'ctoken' => $ctoken,
            'hostname' => 'www.midasbuy.com',
        ];

        $headers = [
            'Origin' => 'https://www.midasbuy.com',
            'Referer' => $referer,
            'User-Agent' => $userAgent,
        ];

        $this->http->get($referer, [
            'Accept' => 'text/html,application/xhtml+xml',
            'User-Agent' => $userAgent,
        ]);

        $response = $this->http->postJson($endpoint, $payload, $headers);
        $data = $response->json();
        $sessionRetried = false;

        if ($this->isChallenge($response, $data)) {
            // Preserve any cookie rotated by the challenge and retry once.
            $response = $this->http->postJson($endpoint, $payload, $headers);
            $data = $response->json();
            $sessionRetried = true;
        }

        $meta = array_merge(ProviderDiagnostics::fromResponse($response, $this->debug), [
            'direct_http' => true,
            'browser_assisted' => false,
            'official_service' => 'Midasbuy getCharac',
            'encryption_session_configured' => true,
            'payload_generated_for_player' => true,
            'managed_session' => true,
            'session_retried' => $sessionRetried,
            'user_agent_configurable' => true,
        ]);

        if ($this->isChallenge($response, $data)) {
            return LookupResult::failure(
                ResultCode::PROVIDER_RESTRICTED,
                'Midasbuy requires browser verification for this server. Trying the next provider is safe.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
                $meta,
            );
        }

        if ($response->status === 429) {
            return LookupResult::failure(ResultCode::RATE_LIMITED, 'Midasbuy rate limit reached.', $game['code'] ?? null, $playerId, $zoneId, $this->key(), $meta);
        }
        if ($response->failed()) {
            return LookupResult::failure(ResultCode::NETWORK_ERROR, $response->error ?? sprintf('Midasbuy returned HTTP %d.', $response->status), $game['code'] ?? null, $playerId, $zoneId, $this->key(), $meta);
        }

        if (!is_array($data)) {
            return LookupResult::failure(ResultCode::INVALID_RESPONSE, 'Midasbuy returned a non-JSON response.', $game['code'] ?? null, $playerId, $zoneId, $this->key(), $meta);
        }

        $nickname = Normalizer::nickname(rawurldecode((string) Arr::firstFilled($data, ['info.charac_name', 'charac_name'])));
        $country = strtoupper(trim((string) Arr::firstFilled($data, ['info.active_country', 'info.register_country', 'country'])));

        if ((int) ($data['ret'] ?? -1) === 0 && $nickname !== null) {
            $meta['game_openid'] = Arr::firstFilled($data, ['info.openid']);
            $meta['zone_id'] = Arr::firstFilled($data, ['info.zoneid']);
            $meta['is_ban'] = (bool) Arr::firstFilled($data, ['info.is_ban']);

            return LookupResult::success(
                (string) ($game['code'] ?? 'pubgmobile'),
                $playerId,
                $nickname,
                $this->key(),
                $zoneId,
                null,
                $meta,
                $country !== '' ? $country : null,
            );
        }

        $message = trim((string) Arr::firstFilled($data, ['msg', 'message', 'error']));
        $expired = (int) ($data['ret'] ?? 0) === 6 || str_contains(strtolower($message), 'ctoken');

        return LookupResult::failure(
            $expired ? ResultCode::PROVIDER_RESTRICTED : ResultCode::INVALID_PLAYER,
            $message !== '' ? $message : 'PUBG Mobile account was not found by Midasbuy.',
            $game['code'] ?? null,
            $playerId,
            $zoneId,
            $this->key(),
            $meta,
        );
    }

    private function isChallenge(HttpResponse $response, mixed $data): bool
    {
        $url = is_array($data) ? strtolower((string) ($data['url'] ?? '')) : '';
        $body = strtolower($response->body);

        return str_contains($url, 'captcha-delivery.com')
            || str_contains($url, 'datadome')
            || (($response->status === 403 || $response->status === 429)
                && (str_contains($body, 'captcha-delivery.com') || str_contains($body, 'datadome')));
    }
}
