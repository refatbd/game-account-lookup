<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Http;

use Refatbd\GameAccountLookup\Contracts\HttpClientInterface;

final class HttpClient implements HttpClientInterface
{
    /** @var array<string, string> */
    private array $cookies = [];

    /**
     * @param callable(string, array<string, mixed>): void|null $logger
     */
    public function __construct(
        private readonly int $timeout = 12,
        private readonly int $connectTimeout = 5,
        private readonly bool $verifyTls = true,
        private readonly mixed $logger = null,
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
        $this->cookies = [];
    }

    /**
     * @param array<string, string> $headers
     */
    private function request(string $method, string $url, array $headers, ?string $body = null): HttpResponse
    {
        $ch = curl_init();

        if ($ch === false) {
            return new HttpResponse(0, '', [], 'Unable to initialise cURL.', 0, $url);
        }

        if ($this->cookies !== [] && !$this->hasHeader($headers, 'Cookie')) {
            $headers['Cookie'] = implode('; ', array_map(
                static fn (string $name, string $value): string => $name . '=' . $value,
                array_keys($this->cookies),
                array_values($this->cookies),
            ));
        }

        $responseHeaders = [];
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        curl_setopt_array($ch, [
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
            CURLOPT_HEADERFUNCTION => function ($curl, string $line) use (&$responseHeaders): int {
                $length = strlen($line);
                $line = trim($line);

                if ($line === '' || !str_contains($line, ':')) {
                    return $length;
                }

                [$name, $value] = explode(':', $line, 2);
                $normalizedName = strtolower(trim($name));
                $value = trim($value);
                $responseHeaders[$normalizedName][] = $value;

                if ($normalizedName === 'set-cookie') {
                    $this->rememberCookie($value);
                }

                return $length;
            },
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $started = microtime(true);
        $responseBody = curl_exec($ch);
        $error = curl_error($ch) ?: null;
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $effectiveUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $durationMs = (int) round((microtime(true) - $started) * 1000);
        curl_close($ch);

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

    private function rememberCookie(string $header): void
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
            unset($this->cookies[$name]);
            return;
        }

        $this->cookies[$name] = $value;
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
