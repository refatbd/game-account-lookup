<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Support;

/**
 * Resolves GoPay Games' current internal product code from public Next.js/RSC
 * storefront markup. Static codes remain supported as a fallback, but newly
 * added products no longer need to depend exclusively on copied code values.
 */
final class GopayMetadataResolver
{
    /** @return array{code:?string,source:string,candidates:list<string>,maintenance:bool} */
    public function resolve(string $html, array $fallbackCandidates = []): array
    {
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(['\\u0026', '\\u003c', '\\u003e', '\\u002F'], ['&', '<', '>', '/'], $text);
        $maintenance = $this->isMaintenance($text);
        $found = [];

        foreach ($this->jsonDocuments($html) as $document) {
            $this->collectCodes($document, '', $found);
        }

        foreach ([
            '/["\\\'](?:gameCode|productCode|product_code|game_code|inquiryCode|accountCode)["\\\']\\s*:\\s*["\\\']([A-Z][A-Z0-9_]{2,80})["\\\']/i',
            '/\\\\"(?:gameCode|productCode|product_code|game_code|inquiryCode|accountCode)\\\\"\\s*:\\s*\\\\"([A-Z][A-Z0-9_]{2,80})\\\\"/i',
            '/\\\\"product\\\\".{0,5000}?\\\\"code\\\\"\\s*:\\s*\\\\"([A-Z][A-Z0-9_]{2,80})\\\\"/is',
            '/(?:user-account|order\/user-account).{0,500}?["\\\']code["\\\']\\s*:\\s*["\\\']([A-Z][A-Z0-9_]{2,80})["\\\']/is',
        ] as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $value) {
                    $this->addCode($found, (string) $value, 100);
                }
            }
        }

        foreach ($fallbackCandidates as $candidate) {
            $this->addCode($found, (string) $candidate, 10);
        }

        arsort($found);
        $candidates = array_keys($found);

        return [
            'code' => $candidates[0] ?? null,
            'source' => $found === [] ? 'none' : (((int) reset($found)) >= 50 ? 'storefront-runtime' : 'configured-fallback'),
            'candidates' => $candidates,
            'maintenance' => $maintenance,
        ];
    }

    public function isMaintenance(string $text): bool
    {
        if (preg_match('/["\']?ismaintenance(?:\\\\)*["\']?\s*:\s*true/i', $text)) {
            return true;
        }

        $haystack = strtolower(strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        foreach ([
            'layanan ini lagi dalam perbaikan',
            'service is currently under maintenance',
            'temporarily unavailable',
            'product is under maintenance',
        ] as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<array<string, mixed>> */
    private function jsonDocuments(string $html): array
    {
        $documents = [];
        if (preg_match_all('/<script[^>]*type=["\\\']application\\/json["\\\'][^>]*>(.*?)<\\/script>/is', $html, $matches)) {
            foreach ($matches[1] as $json) {
                $decoded = json_decode(html_entity_decode((string) $json, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
                if (is_array($decoded)) {
                    $documents[] = $decoded;
                }
            }
        }
        if (preg_match('/<script[^>]*id=["\\\']__NEXT_DATA__["\\\'][^>]*>(.*?)<\\/script>/is', $html, $match)) {
            $decoded = json_decode(html_entity_decode((string) $match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            if (is_array($decoded)) {
                $documents[] = $decoded;
            }
        }

        return $documents;
    }

    /** @param array<string, mixed> $node @param array<string, int> $found */
    private function collectCodes(array $node, string $path, array &$found): void
    {
        foreach ($node as $key => $value) {
            $keyString = (string) $key;
            $childPath = $path === '' ? $keyString : $path . '.' . $keyString;
            if (is_array($value)) {
                $this->collectCodes($value, $childPath, $found);
                continue;
            }
            if (!is_string($value)) {
                continue;
            }

            $normalizedKey = strtolower(preg_replace('/[^a-z0-9]+/i', '', $keyString) ?? '');
            if (in_array($normalizedKey, ['gamecode', 'productcode', 'inquirycode', 'accountcode'], true)) {
                $this->addCode($found, $value, 120);
            } elseif ($normalizedKey === 'code' && preg_match('/(?:game|product|order|account|voucher)/i', $childPath)) {
                $this->addCode($found, $value, 60);
            }
        }
    }

    /** @param array<string, int> $found */
    private function addCode(array &$found, string $value, int $score): void
    {
        $value = strtoupper(trim($value));
        if (!preg_match('/^[A-Z][A-Z0-9_]{2,80}$/', $value)) {
            return;
        }
        if (in_array($value, ['SUCCESS', 'ERROR', 'ACTIVE', 'INACTIVE', 'MAINTENANCE', 'NOVERIFY', 'IDR'], true)) {
            return;
        }
        $found[$value] = max($score, $found[$value] ?? 0);
    }
}
