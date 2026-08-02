<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Support;

/**
 * Extracts current Codashop storefront metadata from server-rendered HTML and
 * embedded JSON/Next.js hydration data.
 *
 * Codashop regularly changes object nesting and regional product metadata.
 * This resolver therefore searches semantically named keys and also keeps
 * price-point fields from one coherent product object whenever possible.
 */
final class CodashopMetadataResolver
{
    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public function resolve(string $html, array $overrides = []): array
    {
        $documents = $this->jsonDocuments($html);
        $flat = [];

        foreach ($documents as $document) {
            $this->flatten($document, '', $flat);
        }

        $candidate = $this->bestPricePointCandidate($documents);

        // Direct text extraction covers hydration formats that are not valid
        // standalone JSON, including escaped React Server Component chunks.
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(
            ['\\u0026', '\\u003c', '\\u003e', '\\u002F'],
            ['&', '<', '>', '/'],
            $text,
        );

        $metadata = [
            'pageLockToken' => $this->value($overrides, ['pageLockToken'])
                ?? $this->fromFlat($flat, ['pagelocktoken', 'page.lock.token'])
                ?? $this->regexString($text, ['pageLockToken', 'page_lock_token'])
                ?? $this->jwt($text),
            'productPath' => $this->value($overrides, ['productPath'])
                ?? $this->fromFlat($flat, ['productinfo.producturl', 'productpath', 'product.path'])
                ?? $this->regexString($text, ['productPath', 'product_path', 'productUrl']),
            'skuId' => $this->value($overrides, ['skuId'])
                ?? ($candidate['skuId'] ?? null)
                ?? $this->fromFlat($flat, ['skuid', 'sku.id', 'sku_id'])
                ?? $this->regexScalar($text, ['skuId', 'sku_id']),
            'paymentChannelId' => $this->value($overrides, ['paymentChannelId', 'pcId'])
                ?? ($candidate['paymentChannelId'] ?? null)
                ?? $this->fromFlat($flat, ['paymentchannelid', 'paymentchannel.id', 'pcid'])
                ?? $this->regexScalar($text, ['paymentChannelId', 'payment_channel_id', 'pcId']),
            'whitelabelId' => $this->value($overrides, ['whitelabelId'])
                ?? $this->fromFlat($flat, ['whitelabelid', 'whiteLabelId'])
                ?? $this->regexScalar($text, ['whitelabelId', 'whiteLabelId'])
                ?? 0,
            'vppId' => $this->value($overrides, ['vppId', 'voucherPricePointId'])
                ?? ($candidate['vppId'] ?? null)
                ?? $this->fromFlat($flat, [
                    'voucherpricepoint.id',
                    'voucherpricepointid',
                    'pricepoint.id',
                    'pricepointid',
                    'vppid',
                ])
                ?? $this->regexScalar($text, ['voucherPricePointId', 'pricePointId', 'vppId']),
            'price' => $this->value($overrides, ['price'])
                ?? ($candidate['price'] ?? null)
                ?? $this->fromFlat($flat, [
                    'voucherpricepoint.price',
                    'pricepoint.price',
                    'sellingprice',
                    'amount.value',
                ])
                ?? $this->regexScalar($text, ['sellingPrice', 'voucherPricePointPrice', 'price']),
            'variablePrice' => $this->value($overrides, ['variablePrice'])
                ?? ($candidate['variablePrice'] ?? null)
                ?? $this->fromFlat($flat, ['voucherpricepoint.variableprice', 'variableprice'])
                ?? 0,
            'voucherTypeName' => $this->value($overrides, ['voucherTypeName'])
                ?? ($candidate['voucherTypeName'] ?? null)
                ?? $this->fromFlat($flat, ['vouchertypename', 'voucher.type.name', 'producttypename'])
                ?? $this->regexString($text, ['voucherTypeName', 'productTypeName']),
            'voucherTypeId' => $this->value($overrides, ['voucherTypeId'])
                ?? ($candidate['voucherTypeId'] ?? null)
                ?? $this->fromFlat($flat, ['vouchertypeid', 'voucher.type.id'])
                ?? $this->regexScalar($text, ['voucherTypeId']),
            'lvtId' => $this->value($overrides, ['lvtId'])
                ?? ($candidate['lvtId'] ?? null)
                ?? $this->fromFlat($flat, ['lvtid'])
                ?? $this->regexScalar($text, ['lvtId']),
            'gvtId' => $this->value($overrides, ['gvtId'])
                ?? ($candidate['gvtId'] ?? null)
                ?? $this->fromFlat($flat, ['gvtid'])
                ?? $this->regexScalar($text, ['gvtId']),
            'shopLang' => $this->value($overrides, ['shopLang'])
                ?? $this->fromFlat($flat, ['shoplang', 'locale.shoplang'])
                ?? $this->regexString($text, ['shopLang']),
            'initEndpoint' => $this->value($overrides, ['initEndpoint', 'endpoint'])
                ?? $this->fromFlat($flat, ['initpaymentaction', 'initpaymenturl', 'urls.initpayment'])
                ?? $this->regexUrl($text, 'initPayment.action'),
            'orderTokenEndpoint' => $this->value($overrides, ['orderTokenEndpoint'])
                ?? $this->fromFlat($flat, ['createordertokenurl', 'ordertokenendpoint'])
                ?? $this->regexUrl($text, 'createOrderToken'),
        ];

        foreach ($metadata as $key => $value) {
            if (is_string($value)) {
                $value = trim(stripslashes($value));
                $metadata[$key] = $value === '' ? null : $value;
            }
        }

        $metadata['discovered_keys'] = array_slice(array_keys($flat), 0, 160);
        $metadata['json_documents'] = count($documents);
        $metadata['price_point_candidate_score'] = $candidate['score'] ?? null;

        return $metadata;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function jsonDocuments(string $html): array
    {
        $documents = [];

        if (preg_match_all('/<script([^>]*)type=["\']application\/json["\']([^>]*)>(.*?)<\/script>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attributes = (string) (($match[1] ?? '') . ($match[2] ?? ''));
                $json = (string) ($match[3] ?? '');
                $decoded = $this->decodeJson((string) $json);
                if ($decoded !== null) {
                    // Nuxt 3 serializes hydration state with devalue: object
                    // properties contain indexes into the top-level array rather
                    // than their actual values. Walking that raw array makes
                    // indexes such as 107 look like a page-lock token and loses
                    // nested SKU/price-point objects entirely.
                    if (preg_match('/\bid=["\']__NUXT_DATA__["\']/i', $attributes)) {
                        $hydrated = $this->hydrateNuxtPayload($decoded);
                        $documents[] = $hydrated ?? $decoded;
                    } else {
                        $documents[] = $decoded;
                    }
                }
            }
        }

        if (preg_match('/<script[^>]*id=["\']__NEXT_DATA__["\'][^>]*>(.*?)<\/script>/is', $html, $match)) {
            $decoded = $this->decodeJson((string) $match[1]);
            if ($decoded !== null) {
                $documents[] = $decoded;
            }
        }

        // React Server Components are often emitted as JSON-escaped strings.
        if (preg_match_all('/self\.__next_f\.push\(\[(?:\d+),\s*"(.+?)"\]\)/s', $html, $chunks)) {
            foreach ($chunks[1] as $chunk) {
                $unescaped = json_decode('"' . $chunk . '"', true);
                if (!is_string($unescaped)) {
                    continue;
                }

                foreach ($this->balancedJsonObjects($unescaped) as $object) {
                    $decoded = $this->decodeJson($object);
                    if ($decoded !== null) {
                        $documents[] = $decoded;
                    }
                }
            }
        }

        // Also scan the complete response for balanced JSON objects that carry
        // one of the metadata keys. This handles inline assignment scripts.
        foreach ($this->balancedJsonObjects($html) as $object) {
            if (!preg_match('/(?:skuId|voucherPricePoint|paymentChannelId|productPath|pageLockToken)/i', $object)) {
                continue;
            }

            $decoded = $this->decodeJson($object);
            if ($decoded !== null) {
                $documents[] = $decoded;
            }
        }

        return $documents;
    }

    /**
     * Reconstruct a Nuxt/devalue payload whose nested values are references to
     * entries in one top-level array.
     *
     * @param array<string|int, mixed> $payload
     * @return array<string, mixed>|null
     */
    private function hydrateNuxtPayload(array $payload): ?array
    {
        if (!array_is_list($payload) || $payload === []) {
            return null;
        }

        $resolving = [];
        $resolved = [];
        $value = $this->hydrateNuxtReference(0, $payload, $resolving, $resolved);

        return is_array($value) ? $value : null;
    }

    /**
     * @param list<mixed> $payload
     * @param array<int, true> $resolving
     * @param array<int, mixed> $resolved
     */
    private function hydrateNuxtReference(int $index, array $payload, array &$resolving, array &$resolved): mixed
    {
        // Negative devalue indexes represent undefined/non-finite sentinel
        // values. None are useful as provider request metadata.
        if ($index < 0 || !array_key_exists($index, $payload)) {
            return null;
        }

        if (array_key_exists($index, $resolved)) {
            return $resolved[$index];
        }

        if (isset($resolving[$index])) {
            return null;
        }

        $resolving[$index] = true;
        $value = $payload[$index];

        if (is_array($value)) {
            if ($this->isNuxtReactiveWrapper($value)) {
                $value = $this->hydrateNuxtValue($value[1], $payload, $resolving, $resolved);
            } else {
                $hydrated = [];
                foreach ($value as $key => $item) {
                    $hydrated[$key] = $this->hydrateNuxtValue($item, $payload, $resolving, $resolved);
                }
                $value = $hydrated;
            }
        }

        unset($resolving[$index]);
        $resolved[$index] = $value;

        return $value;
    }

    /**
     * @param list<mixed> $payload
     * @param array<int, true> $resolving
     * @param array<int, mixed> $resolved
     */
    private function hydrateNuxtValue(mixed $value, array $payload, array &$resolving, array &$resolved): mixed
    {
        if (is_int($value)) {
            return $this->hydrateNuxtReference($value, $payload, $resolving, $resolved);
        }

        if (!is_array($value)) {
            return $value;
        }

        $hydrated = [];
        foreach ($value as $key => $item) {
            $hydrated[$key] = $this->hydrateNuxtValue($item, $payload, $resolving, $resolved);
        }

        return $hydrated;
    }

    /** @param array<int|string, mixed> $value */
    private function isNuxtReactiveWrapper(array $value): bool
    {
        return array_is_list($value)
            && count($value) === 2
            && is_string($value[0] ?? null)
            && in_array($value[0], ['Reactive', 'ShallowReactive', 'Ref', 'ShallowRef'], true);
    }

    /** @return array<string, mixed>|null */
    private function decodeJson(string $json): ?array
    {
        $json = html_entity_decode(trim($json), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($json === '') {
            return null;
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (\JsonException) {
            return null;
        }
    }

    /**
     * @param list<array<string, mixed>> $documents
     * @return array<string, mixed>
     */
    private function bestPricePointCandidate(array $documents): array
    {
        $candidates = [];
        foreach ($documents as $document) {
            $this->collectPricePointCandidates($document, '', [], $candidates);
        }

        if ($candidates === []) {
            return [];
        }

        $paymentChannelIds = $this->preferredPaymentChannelIds($documents);

        usort($candidates, static function (array $a, array $b) use ($paymentChannelIds): int {
            $score = ((int) ($b['score'] ?? 0)) <=> ((int) ($a['score'] ?? 0));
            if ($score !== 0) {
                return $score;
            }

            $aPrice = is_numeric($a['price'] ?? null) ? (float) $a['price'] : PHP_FLOAT_MAX;
            $bPrice = is_numeric($b['price'] ?? null) ? (float) $b['price'] : PHP_FLOAT_MAX;

            $price = $aPrice <=> $bPrice;
            if ($price !== 0) {
                return $price;
            }

            $aRank = array_search($a['paymentChannelId'] ?? null, $paymentChannelIds, true);
            $bRank = array_search($b['paymentChannelId'] ?? null, $paymentChannelIds, true);

            return ($aRank === false ? PHP_INT_MAX : $aRank)
                <=> ($bRank === false ? PHP_INT_MAX : $bRank);
        });

        return $candidates[0];
    }

    /**
     * @param list<array<string, mixed>> $documents
     * @return list<string|int>
     */
    private function preferredPaymentChannelIds(array $documents): array
    {
        foreach ($documents as $document) {
            $ids = $this->findPaymentChannelIds($document);
            if ($ids !== []) {
                return $ids;
            }
        }

        return [];
    }

    /** @param array<string, mixed> $node @return list<string|int> */
    private function findPaymentChannelIds(array $node): array
    {
        foreach ($node as $key => $value) {
            if (strcasecmp((string) $key, 'paymentChannels') === 0 && is_array($value)) {
                $ids = [];
                foreach ($value as $channel) {
                    if (!is_array($channel) || strtoupper((string) ($channel['status'] ?? 'ACTIVE')) !== 'ACTIVE') {
                        continue;
                    }
                    $id = $this->caseInsensitiveValue($channel, ['id', 'paymentChannelId']);
                    if (is_string($id) || is_int($id)) {
                        $ids[] = $id;
                    }
                }
                if ($ids !== []) {
                    return $ids;
                }
            }

            if (is_array($value)) {
                $ids = $this->findPaymentChannelIds($value);
                if ($ids !== []) {
                    return $ids;
                }
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $node
     * @param array<string, mixed> $context
     * @param list<array<string, mixed>> $candidates
     */
    private function collectPricePointCandidates(array $node, string $path, array $context, array &$candidates): void
    {
        $localContext = array_merge($context, $this->scalarContext($node));
        $normalizedPath = strtolower($path);

        $pricePoint = $this->caseInsensitiveValue($node, ['voucherPricePoint', 'pricePoint']);
        if (is_array($pricePoint)) {
            $candidate = $this->candidateFromNode($pricePoint, $localContext, $normalizedPath . '.voucherPricePoint');
            if ($candidate !== []) {
                $candidates[] = $candidate;
            }
        }

        if (str_contains($normalizedPath, 'pricepoint') || $this->hasAnyKey($node, ['vppId', 'voucherPricePointId'])) {
            $candidate = $this->candidateFromNode($node, $localContext, $normalizedPath);
            if ($candidate !== []) {
                $candidates[] = $candidate;
            }
        }

        foreach ($node as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            $childPath = $path === '' ? (string) $key : $path . '.' . $key;
            if (array_is_list($value)) {
                foreach ($value as $index => $item) {
                    if (is_array($item)) {
                        $this->collectPricePointCandidates($item, $childPath . '.' . $index, $localContext, $candidates);
                    }
                }
                continue;
            }

            $this->collectPricePointCandidates($value, $childPath, $localContext, $candidates);
        }
    }

    /**
     * @param array<string, mixed> $node
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function candidateFromNode(array $node, array $context, string $path): array
    {
        $id = $this->caseInsensitiveValue($node, ['id', 'vppId', 'voucherPricePointId', 'pricePointId']);
        $price = $this->caseInsensitiveValue($node, ['price', 'sellingPrice', 'amount']);

        if (is_array($price)) {
            $price = $this->caseInsensitiveValue($price, ['value', 'amount']);
        }

        if (($id === null || $id === '') || ($price === null || $price === '')) {
            return [];
        }

        $score = str_contains(strtolower($path), 'voucherpricepoint') ? 20 : 12;
        if (is_numeric($id)) {
            $score += 3;
        }
        if (is_numeric($price)) {
            $score += 3;
        }

        $candidate = [
            'vppId' => $id,
            'price' => $price,
            'variablePrice' => $this->caseInsensitiveValue($node, ['variablePrice']) ?? 0,
            'skuId' => $this->firstContextValue($node, $context, ['skuId', 'sku_id', 'displayId']),
            'paymentChannelId' => $this->firstContextValue($node, $context, ['paymentChannelId', 'pcId']),
            'voucherTypeName' => $this->firstContextValue($node, $context, ['voucherTypeName', 'productTypeName']),
            'voucherTypeId' => $this->firstContextValue($node, $context, ['voucherTypeId']),
            'lvtId' => $this->firstContextValue($node, $context, ['lvtId']),
            'gvtId' => $this->firstContextValue($node, $context, ['gvtId']),
            'score' => $score,
        ];

        return array_filter($candidate, static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /** @param array<string, mixed> $node @return array<string, mixed> */
    private function scalarContext(array $node): array
    {
        $context = [];
        foreach ($node as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $context[strtolower((string) $key)] = $value;
            }
        }

        return $context;
    }

    /** @param array<string, mixed> $node @param list<string> $keys */
    private function hasAnyKey(array $node, array $keys): bool
    {
        foreach ($keys as $key) {
            foreach (array_keys($node) as $existing) {
                if (strcasecmp((string) $existing, $key) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @param array<string, mixed> $node @param list<string> $keys */
    private function caseInsensitiveValue(array $node, array $keys): mixed
    {
        foreach ($keys as $key) {
            foreach ($node as $existing => $value) {
                if (strcasecmp((string) $existing, $key) === 0 && $value !== null && $value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    /** @param array<string, mixed> $node @param array<string, mixed> $context @param list<string> $keys */
    private function firstContextValue(array $node, array $context, array $keys): mixed
    {
        $value = $this->caseInsensitiveValue($node, $keys);
        if ($value !== null && $value !== '') {
            return $value;
        }

        foreach ($keys as $key) {
            $normalized = strtolower($key);
            if (array_key_exists($normalized, $context) && $context[$normalized] !== null && $context[$normalized] !== '') {
                return $context[$normalized];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $value
     * @param array<string, scalar|null> $flat
     */
    private function flatten(array $value, string $prefix, array &$flat): void
    {
        foreach ($value as $key => $item) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($item)) {
                $this->flatten($item, $path, $flat);
                continue;
            }

            if (is_scalar($item) || $item === null) {
                $flat[strtolower($path)] = $item;
            }
        }
    }

    /**
     * @param array<string, scalar|null> $flat
     * @param list<string> $needles
     */
    private function fromFlat(array $flat, array $needles): string|int|float|bool|null
    {
        foreach ($needles as $needle) {
            $needle = strtolower($needle);
            foreach ($flat as $path => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                
                $pathStr = (string) $path;

                if ($pathStr === $needle || str_ends_with($pathStr, '.' . $needle) || str_ends_with($pathStr, $needle)) {
                    return $value;
                }
            }
        }

        return null;
    }

    /** @param array<string, mixed> $values @param list<string> $keys */
    private function value(array $values, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $values) && $values[$key] !== null && $values[$key] !== '') {
                return $values[$key];
            }
        }

        return null;
    }

    /** @param list<string> $keys */
    private function regexScalar(string $text, array $keys): string|int|float|null
    {
        foreach ($keys as $key) {
            $quoted = preg_quote($key, '/');
            if (preg_match('/["\']' . $quoted . '["\']\s*[:=]\s*["\']?([A-Za-z0-9_.-]+)["\']?/i', $text, $match)) {
                $value = $match[1];
                return is_numeric($value) ? (str_contains($value, '.') ? (float) $value : (int) $value) : $value;
            }
        }

        return null;
    }

    /** @param list<string> $keys */
    private function regexString(string $text, array $keys): ?string
    {
        foreach ($keys as $key) {
            $quoted = preg_quote($key, '/');
            if (preg_match('/["\']' . $quoted . '["\']\s*[:=]\s*["\']([^"\']+)["\']/i', $text, $match)) {
                return $match[1];
            }
        }

        return null;
    }

    private function regexUrl(string $text, string $suffix): ?string
    {
        $suffix = preg_quote($suffix, '/');
        if (preg_match('/https?:\\?\/\\?\/[^"\'\s]+?' . $suffix . '/i', $text, $match)) {
            return str_replace('\\/', '/', $match[0]);
        }

        return null;
    }

    private function jwt(string $text): ?string
    {
        if (preg_match('/eyJ[A-Za-z0-9_-]{20,}\.[A-Za-z0-9_-]{20,}\.[A-Za-z0-9_-]{10,}/', $text, $match)) {
            return $match[0];
        }

        return null;
    }

    /**
     * Extract a bounded set of balanced JSON object candidates.
     *
     * @return list<string>
     */
    private function balancedJsonObjects(string $text): array
    {
        $objects = [];
        $length = strlen($text);
        $start = null;
        $depth = 0;
        $quoted = false;
        $escaped = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $text[$i];

            if ($quoted) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $quoted = false;
                }
                continue;
            }

            if ($char === '"') {
                $quoted = true;
                continue;
            }

            if ($char === '{') {
                if ($depth === 0) {
                    $start = $i;
                }
                $depth++;
                continue;
            }

            if ($char === '}' && $depth > 0) {
                $depth--;
                if ($depth === 0 && $start !== null) {
                    $candidate = substr($text, $start, $i - $start + 1);
                    if (strlen($candidate) <= 500000) {
                        $objects[] = $candidate;
                    }
                    if (count($objects) >= 100) {
                        break;
                    }
                    $start = null;
                }
            }
        }

        return $objects;
    }
}
