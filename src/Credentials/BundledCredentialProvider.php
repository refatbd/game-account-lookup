<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Credentials;

/**
 * Bundled non-cookie credentials for this single-owner private build.
 *
 * Keep bundled request-encryption values in this class only. Never expose them in
 * API responses, logs, frontend code, fixtures, or documentation.
 */
final class BundledCredentialProvider implements CredentialProviderInterface
{
    /** @var array<string, string> */
    private const MIDASBUY = [
        'encryptionKey' => '19fbd6f19ebf247078b1c5c3ac3b3ea5bac2cacec43f36973bda3e648c259f79',
        'encryptionIv' => '1234567890123456',
        'ctokenVersion' => '1.0.1',
        'ctoken' => '37570a40820a30494e8c690745ee1078a197e5b76a094aad2c3b5171ee6c0243d67713d78a08a506a10844552007b9ea',
    ];

    public function forProvider(string $provider): ?ProviderCredential
    {
        $provider = strtolower(trim($provider));
        $values = match ($provider) {
            'midasbuy' => self::MIDASBUY,
            default => null,
        };

        return is_array($values) ? new ProviderCredential($provider, $values) : null;
    }
}
