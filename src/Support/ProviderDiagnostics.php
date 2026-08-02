<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Support;

use Refatbd\GameAccountLookup\Http\HttpResponse;

final class ProviderDiagnostics
{
    /**
     * Return a deliberately small and sanitized diagnostic payload that is safe
     * to expose in the local tester. Authentication headers and full upstream
     * bodies are never included.
     *
     * @return array<string, mixed>
     */
    public static function fromResponse(HttpResponse $response, bool $includePreview = false): array
    {
        $contentType = self::firstHeader($response, 'content-type');
        $rayId = self::firstHeader($response, 'cf-ray');
        $retryAfter = self::firstHeader($response, 'retry-after');

        $meta = [
            'http_status' => $response->status,
            'duration_ms' => $response->durationMs,
            'content_type' => $contentType,
            'upstream_host' => self::host($response->effectiveUrl),
        ];

        if ($rayId !== null) {
            $meta['ray_id'] = $rayId;
        }

        if ($retryAfter !== null) {
            $meta['retry_after'] = $retryAfter;
        }

        if ($response->error !== null && $response->error !== '') {
            $meta['transport_error'] = self::cleanText($response->error, 300);
        }

        if ($includePreview && $response->body !== '') {
            $meta['response_preview'] = self::bodyPreview($response->body, $contentType);
        }

        return array_filter(
            $meta,
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }

    private static function firstHeader(HttpResponse $response, string $name): ?string
    {
        $values = $response->headers[strtolower($name)] ?? [];

        return isset($values[0]) && trim((string) $values[0]) !== ''
            ? trim((string) $values[0])
            : null;
    }

    private static function host(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : null;
    }

    private static function bodyPreview(string $body, ?string $contentType): string
    {
        $preview = $body;

        if ($contentType !== null && str_contains(strtolower($contentType), 'html')) {
            $preview = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/is', '', $preview) ?? $preview;
            $preview = strip_tags($preview);
        }

        return self::cleanText($preview, 700);
    }

    private static function cleanText(string $value, int $limit): string
    {
        // Do not surface JWT-like strings or long bearer/token values in debug output.
        // Normalize JSON-escaped quotes first so the same redaction rules work
        // for HTML, JSON, and React/Next.js serialized response fragments.
        $value = str_replace(['\\"', "\\'"], ['"', "'"], $value);
        $value = preg_replace('/eyJ[A-Za-z0-9_-]{20,}\.[A-Za-z0-9_-]{20,}\.[A-Za-z0-9_-]{10,}/', '[redacted-token]', $value) ?? $value;
        $value = preg_replace('/("[^"]*(?:token|secret|authorization|cookie)[^"]*"\s*:\s*")[^"]*(")/i', '$1[redacted]$2', $value) ?? $value;
        $value = preg_replace('/((?:bearer|token|secret|authorization|cookie)\s*[:=]\s*)[A-Za-z0-9._~+\/-]{8,}/i', '$1[redacted]', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $limit);
        }

        return substr($value, 0, $limit);
    }

    private function __construct()
    {
    }
}
