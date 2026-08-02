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
use Refatbd\GameAccountLookup\ResultCode;
use Refatbd\GameAccountLookup\Support\Arr;
use Refatbd\GameAccountLookup\Support\Normalizer;
use Refatbd\GameAccountLookup\Support\ProviderDiagnostics;

final class GarenaProvider implements ProviderInterface
{
    private const ENDPOINT = 'https://shop2game.com/api/auth/player_id_login';

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
        $headers = [
            'Accept' => 'application/json',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Origin' => 'https://shop2game.com',
            'Referer' => (string) ($config['referer'] ?? 'https://shop2game.com/app/100067/idlogin'),
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 11; Redmi Note 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Mobile Safari/537.36',
            'sec-ch-ua' => '"Chromium";v="107", "Not=A?Brand";v="24"',
            'sec-ch-ua-mobile' => '?1',
            'sec-ch-ua-platform' => '"Android"',
        ];
        if ($cookie !== '') {
            $headers['Cookie'] = $cookie;
        }
        if ($dataDomeClientId !== '') {
            $headers['x-datadome-clientid'] = $dataDomeClientId;
        }

        $response = $this->http->postJson(
            (string) ($config['endpoint'] ?? self::ENDPOINT),
            [
                'app_id' => (int) ($config['appId'] ?? 100067),
                'login_id' => $playerId,
                'app_server_id' => (int) ($config['appServerId'] ?? 0),
            ],
            $headers,
        );
        $meta = array_merge(ProviderDiagnostics::fromResponse($response, $response->failed()), [
            'official_service' => 'Garena Shop2Game',
            'country_source' => 'region',
            'challenge_credentials_configured' => $cookie !== '' || $dataDomeClientId !== '',
        ]);

        if ($response->status === 429) {
            return LookupResult::failure(ResultCode::RATE_LIMITED, 'Garena Shop2Game rate limit reached.', $game['code'] ?? null, $playerId, $zoneId, $this->key(), $meta);
        }

        $data = $response->json();
        if (is_array($data) && $this->isChallenge($data)) {
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

    /** @param array<string, mixed> $data */
    private function isChallenge(array $data): bool
    {
        $url = strtolower((string) ($data['url'] ?? ''));

        return str_contains($url, 'captcha-delivery.com') || str_contains($url, 'datadome');
    }
}
