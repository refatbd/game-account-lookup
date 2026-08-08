<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Providers;

use Refatbd\GameAccountLookup\Credentials\CredentialProviderInterface;
use Refatbd\GameAccountLookup\Credentials\EnvironmentCredentialProvider;
use Refatbd\GameAccountLookup\Contracts\HttpClientInterface;
use Refatbd\GameAccountLookup\Contracts\ProviderInterface;
use Refatbd\GameAccountLookup\Contracts\SessionAwareHttpClientInterface;
use Refatbd\GameAccountLookup\DTO\LookupResult;
use Refatbd\GameAccountLookup\Http\HttpResponse;
use Refatbd\GameAccountLookup\ResultCode;
use Refatbd\GameAccountLookup\Support\Arr;
use Refatbd\GameAccountLookup\Support\Normalizer;
use Refatbd\GameAccountLookup\Support\ProviderDiagnostics;
use Refatbd\GameAccountLookup\Support\UserAgent;

final class GarenaProvider implements ProviderInterface
{
    private const ENDPOINT = 'https://shop2game.com/api/auth/player_id_login';
    private const LOGIN_PAGE = 'https://shop2game.com/app/100067/idlogin';
    private const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Linux; Android 11; Mobile) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Mobile Safari/537.36';

    private readonly CredentialProviderInterface $credentials;

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly bool $debug = false,
        ?CredentialProviderInterface $credentials = null,
    ) {
        $this->credentials = $credentials ?? new EnvironmentCredentialProvider();
    }

    public function key(): string
    {
        return 'garena';
    }

    public function supports(array $game): bool
    {
        $config = $game['providers'][$this->key()] ?? null;

        return is_array($config) && ($config['enabled'] ?? true) === true;
    }

    public function lookup(array $game, string $playerId, ?string $zoneId = null): LookupResult
    {
        $config = (array) ($game['providers'][$this->key()] ?? []);
        if (!$this->supports($game)) {
            return LookupResult::failure(
                ResultCode::PROVIDER_NOT_CONFIGURED,
                'Garena Shop2Game is not configured or is disabled.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
            );
        }

        if (!ctype_digit($playerId) || strlen($playerId) < 8 || strlen($playerId) > 12) {
            return LookupResult::failure(
                ResultCode::INVALID_PLAYER,
                'Free Fire UID must contain 8 to 12 digits.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
            );
        }

        $credential = $this->credentials->forProvider($this->key());
        $cookie = trim((string) ($credential?->value('cookie') ?? ''));
        $dataDomeClientId = trim((string) ($credential?->value('dataDomeClientId') ?? ''));
        $referer = (string) ($config['referer'] ?? self::LOGIN_PAGE);
        $userAgent = UserAgent::resolve(
            'GAME_LOOKUP_GARENA_USER_AGENT',
            self::DEFAULT_USER_AGENT,
            $config['userAgent'] ?? null,
        );
        $headers = [
            'Accept' => 'application/json',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Origin' => 'https://shop2game.com',
            'Referer' => $referer,
            'User-Agent' => $userAgent,
        ];

        $sessionAware = $this->http instanceof SessionAwareHttpClientInterface;
        $preflightSkipped = $sessionAware && $this->http->hasWarmSession($referer);
        $preflightHeaders = [
            'Accept' => 'text/html,application/xhtml+xml',
            'Accept-Language' => 'en-US,en;q=0.9',
            'User-Agent' => $userAgent,
        ];

        // A persisted, verified session lets later unique UIDs go straight to
        // the player endpoint. Cold sessions retain the normal page preflight.
        if (!$preflightSkipped) {
            $this->http->get($referer, $preflightHeaders);
        }

        $requestHeaders = $headers;
        if ($cookie !== '') {
            $requestHeaders['Cookie'] = $cookie;
        }
        if ($dataDomeClientId !== '') {
            $requestHeaders['x-datadome-clientid'] = $dataDomeClientId;
        }

        $response = $this->http->postJson(
            (string) ($config['endpoint'] ?? self::ENDPOINT),
            [
                'app_id' => (int) ($config['appId'] ?? 100067),
                'login_id' => $playerId,
                'app_server_id' => (int) ($config['appServerId'] ?? 0),
            ],
            $requestHeaders,
        );
        $data = $response->json();
        $sessionRetried = false;

        if ($this->isChallenge($response, $data)) {
            if ($preflightSkipped) {
                // The cached session became stale. Restore the complete cold
                // flow before retrying so latency improves without weakening
                // provider reliability.
                $this->http->get($referer, $preflightHeaders);
            }

            // A challenge response can rotate the managed cookie. Retry once
            // without stale explicit credentials so the new cookie is used.
            $response = $this->http->postJson(
                (string) ($config['endpoint'] ?? self::ENDPOINT),
                [
                    'app_id' => (int) ($config['appId'] ?? 100067),
                    'login_id' => $playerId,
                    'app_server_id' => (int) ($config['appServerId'] ?? 0),
                ],
                $headers,
            );
            $data = $response->json();
            $sessionRetried = true;

            // A stale cached session may need the same one extra rotated-cookie
            // retry used by a cold session after its preflight.
            if ($preflightSkipped && $this->isChallenge($response, $data)) {
                $response = $this->http->postJson(
                    (string) ($config['endpoint'] ?? self::ENDPOINT),
                    [
                        'app_id' => (int) ($config['appId'] ?? 100067),
                        'login_id' => $playerId,
                        'app_server_id' => (int) ($config['appServerId'] ?? 0),
                    ],
                    $headers,
                );
                $data = $response->json();
            }
        }

        if ($sessionAware) {
            if ($this->isChallenge($response, $data)) {
                $this->http->forgetSession($referer);
            } elseif ($response->successful()) {
                $this->http->markSessionWarm($referer);
            }
        }

        $meta = array_merge(ProviderDiagnostics::fromResponse($response, $response->failed()), [
            'official_service' => 'Garena Shop2Game',
            'country_source' => 'region',
            'challenge_credentials_configured' => $cookie !== '' || $dataDomeClientId !== '',
            'managed_session' => true,
            'preflight_skipped' => $preflightSkipped,
            'session_retried' => $sessionRetried,
            'user_agent_configurable' => true,
        ]);

        if ($response->status === 429) {
            return LookupResult::failure(ResultCode::RATE_LIMITED, 'Garena Shop2Game rate limit reached.', $game['code'] ?? null, $playerId, $zoneId, $this->key(), $meta);
        }

        if ($this->isChallenge($response, $data)) {
            return LookupResult::failure(
                ResultCode::PROVIDER_RESTRICTED,
                'Garena Shop2Game requires browser verification for this server. Trying the next provider is safe.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
                $meta,
            );
        }

        if ($response->failed()) {
            return LookupResult::failure(
                ResultCode::NETWORK_ERROR,
                $response->error ?? sprintf('Garena Shop2Game returned HTTP %d.', $response->status),
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
                $meta,
            );
        }

        if (!is_array($data)) {
            return LookupResult::failure(ResultCode::INVALID_RESPONSE, 'Garena Shop2Game returned an empty or non-JSON response.', $game['code'] ?? null, $playerId, $zoneId, $this->key(), $meta);
        }

        $nickname = Normalizer::nickname(Arr::firstFilled($data, ['nickname', 'data.nickname', 'player.nickname']));
        $country = strtoupper(trim((string) Arr::firstFilled($data, ['region', 'country', 'data.region', 'data.country'])));
        $country = $country !== '' ? $country : null;

        if ($nickname !== null) {
            if ($this->debug) {
                $meta['response_fields'] = array_keys($data);
            }

            return LookupResult::success(
                (string) ($game['code'] ?? 'freefire'),
                $playerId,
                $nickname,
                $this->key(),
                $zoneId,
                null,
                $meta,
                $country,
            );
        }

        $message = trim((string) Arr::firstFilled($data, ['message', 'error', 'errorMsg', 'data.message']));
        if ($message === '') {
            $message = 'Free Fire account was not found.';
        }

        return LookupResult::failure(
            $message === 'error_params' ? ResultCode::INVALID_RESPONSE : ResultCode::INVALID_PLAYER,
            $message,
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
