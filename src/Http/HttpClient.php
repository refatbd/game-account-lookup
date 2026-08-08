<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Http;

use Refatbd\GameAccountLookup\Contracts\CacheInterface;
use Refatbd\GameAccountLookup\Contracts\HttpClientInterface;
use Refatbd\GameAccountLookup\Contracts\SessionAwareHttpClientInterface;

final class HttpClient implements HttpClientInterface, SessionAwareHttpClientInterface
{
    /** @var array<string, array<string, string>> */
    private array $cookies = [];

    /** @var array<string, bool> */
    private array $warmSessions = [];

    /** @var array<string, bool> */
    private array $loadedHosts = [];

    private mixed $curlHandle = null;

    /**
     * @param callable(string, array<string, mixed>): void|null $logger
     * @param callable(string, string, array<string, string>, ?string): HttpResponse|null $transport
     */
    public function __construct(
        private readonly int $timeout = 12,
        private readonly int $connectTimeout = 5,
        private readonly bool $verifyTls = true,
        private readonly mixed $logger = null,
        private readonly ?CacheInterface $sessionCache = null,
        private readonly int $sessionTtl = 1800,
        private readonly mixed $transport = null,
    ) {
    }

    /**
     * @param array<string, string> $headers
     */
    public function get(string $url, array $headers = []): HttpResponse
    {
        return $this->request('GET', $url, $headers);
    }

    /**
     * @param array<string, scalar|null> $form
     * @param array<string, string> $headers
     */
    public function postForm(string $url, array $form, array $headers = []): HttpResponse
    {
        $headers['Content-Type'] ??= 'application/x-www-form-urlencoded';

        return $this->request('POST', $url, $headers, http_build_query($form, '', '&', PHP_QUERY_RFC3986));
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    public function postJson(string $url, array $payload, array $headers = []): HttpResponse
    {
        $headers['Content-Type'] ??= 'application/json';
        $headers['Accept'] ??= 'application/json';

        return $this->request(
            'POST',
            $url,
            $headers,
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );
    }

    /**
     * Reset the lightweight in-memory cookie jar.
     */
    public function clearCookies(): void
    {
        // Clear this client's active jar without destroying verified sessions
        // persisted for later package instances. Keeping loadedHosts prevents a
        // same-request profile reset from immediately reloading those cookies.
        $this->cookies = [];
        $this->warmSessions = [];
    }

    public function hasWarmSession(string $url): bool
    {
        $host = $this->host($url);
        $this->loadSession($host);

        return $host !== '' && ($this->warmSessions[$host] ?? false) && ($this->cookies[$host] ?? []) !== [];
    }

    public function markSessionWarm(string $url): void
    {
        $host = $this->host($url);
        $this->loadSession($host);
        if ($host === '' || ($this->cookies[$host] ?? []) === []) {
            return;
        }

        $this->warmSessions[$host] = true;
        $this->persistSession($host);
    }

    public function forgetSession(string $url): void
    {
        $host = $this->host($url);
        if ($host === '') {
            return;
        }

        unset($this->cookies[$host], $this->warmSessions[$host], $this->loadedHosts[$host]);
        $this->sessionCache?->forget($this->sessionCacheKey($host));
    }

    /**
     * @param array<string, string> $headers
     */
    private function request(string $method, string $url, array $headers, ?string $body = null): HttpResponse
    {
        $cookieHost = $this->host($url);
        $this->loadSession($cookieHost);
        $hostCookies = $cookieHost !== '' ? ($this->cookies[$cookieHost] ?? []) : [];
        if ($hostCookies !== [] && !$this->hasHeader($headers, 'Cookie')) {
            $headers['Cookie'] = implode('; ', array_map(
                static fn (string $name, string $value): string => $name . '=' . $value,
                array_keys($hostCookies),
                array_values($hostCookies),
            ));
        }

        if (is_callable($this->transport)) {
            $response = ($this->transport)($method, $url, $headers, $body);
            foreach ((array) ($response->headers['set-cookie'] ?? []) as $cookie) {
                $this->rememberCookie($cookieHost, (string) $cookie);
            }
            $this->persistSession($cookieHost);

            return $response;
        }

        $this->curlHandle ??= curl_init();
        if ($this->curlHandle === false) {
            return new HttpResponse(0, '', [], 'Unable to initialise cURL.', 0, $url);
        }
        curl_reset($this->curlHandle);

        $responseHeaders = [];
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        curl_setopt_array($this->curlHandle, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => $this->verifyTls,
            CURLOPT_SSL_VERIFYHOST => $this->verifyTls ? 2 : 0,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_ENCODING => '',
            CURLOPT_HEADERFUNCTION => function ($curl, string $line) use (&$responseHeaders, $cookieHost): int {
                $length = strlen($line);
                $line = trim($line);

                if ($line === '' || !str_contains($line, ':')) {
                    return $length;
                }

                [$name, $value] = explode(':', $line, 2);
                $normalizedName = strtolower(trim($name));
                $value = trim($value);
                $responseHeaders[$normalizedName][] = $value;

                if ($normalizedName === 'set-cookie' && $cookieHost !== '') {
                    $this->rememberCookie($cookieHost, $value);
                }

                return $length;
            },
        ]);

        if ($body !== null) {
            curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $body);
        }

        $started = microtime(true);
        $responseBody = curl_exec($this->curlHandle);
        $error = curl_error($this->curlHandle) ?: null;
        $status = (int) curl_getinfo($this->curlHandle, CURLINFO_RESPONSE_CODE);
        $effectiveUrl = (string) curl_getinfo($this->curlHandle, CURLINFO_EFFECTIVE_URL);
        $durationMs = (int) round((microtime(true) - $started) * 1000);
        $this->persistSession($cookieHost);

        $this->log('http.request', [
            'method' => $method,
            'url' => $url,
            'status' => $status,
            'duration_ms' => $durationMs,
            'error' => $error,
        ]);

        return new HttpResponse(
            $status,
            is_string($responseBody) ? $responseBody : '',
            $responseHeaders,
            $error,
            $durationMs,
            $effectiveUrl !== '' ? $effectiveUrl : $url,
        );
    }

    /** @param array<string, string> $headers */
    private function hasHeader(array $headers, string $expected): bool
    {
        foreach (array_keys($headers) as $name) {
            if (strcasecmp((string) $name, $expected) === 0) {
                return true;
            }
        }

        return false;
    }

    private function rememberCookie(string $host, string $header): void
    {
        $first = trim(explode(';', $header, 2)[0] ?? '');
        if ($first === '' || !str_contains($first, '=')) {
            return;
        }

        [$name, $value] = explode('=', $first, 2);
        $name = trim($name);
        if ($name === '') {
            return;
        }

        if ($value === '') {
            unset($this->cookies[$host][$name]);
            if (($this->cookies[$host] ?? []) === []) {
                unset($this->cookies[$host]);
            }
            return;
        }

        $this->cookies[$host][$name] = $value;
    }

    private function loadSession(string $host): void
    {
        if ($host === '' || isset($this->loadedHosts[$host])) {
            return;
        }

        $this->loadedHosts[$host] = true;
        $session = $this->sessionCache?->get($this->sessionCacheKey($host));
        if (!is_array($session)) {
            return;
        }

        $cookies = $session['cookies'] ?? null;
        if (is_array($cookies)) {
            $this->cookies[$host] = array_filter(
                array_map('strval', $cookies),
                static fn (string $value): bool => $value !== '',
            );
        }
        $this->warmSessions[$host] = (bool) ($session['warm'] ?? false);
    }

    private function persistSession(string $host): void
    {
        if ($host === '' || $this->sessionCache === null || ($this->cookies[$host] ?? []) === []) {
            return;
        }

        $this->sessionCache->put($this->sessionCacheKey($host), [
            'cookies' => $this->cookies[$host],
            'warm' => (bool) ($this->warmSessions[$host] ?? false),
        ], max(60, $this->sessionTtl));
    }

    private function host(string $url): string
    {
        return strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
    }

    private function sessionCacheKey(string $host): string
    {
        return 'game-account-lookup:http-session:'.hash('sha256', $host);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function log(string $event, array $context): void
    {
        if (is_callable($this->logger)) {
            ($this->logger)($event, $context);
        }
    }
}
