<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Credentials;

/** Complete environment groups override their bundled provider group. */
final class EnvironmentCredentialProvider implements CredentialProviderInterface
{
    public function __construct(private readonly string $prefix = 'GAME_LOOKUP')
    {
    }

    public function forProvider(string $provider): ?ProviderCredential
    {
        $provider = strtolower(trim($provider));
        $values = match ($provider) {
            'garena' => $this->read([
                'cookie' => "{$this->prefix}_GARENA_COOKIE",
                'dataDomeClientId' => "{$this->prefix}_GARENA_DATADOME_CLIENT_ID",
            ]),
            'midasbuy' => $this->read([
                'encryptionKey' => "{$this->prefix}_MIDASBUY_ENCRYPTION_KEY",
                'encryptionIv' => "{$this->prefix}_MIDASBUY_ENCRYPTION_IV",
                'ctokenVersion' => "{$this->prefix}_MIDASBUY_CTOKEN_VERSION",
                'ctoken' => "{$this->prefix}_MIDASBUY_CTOKEN",
            ]),
            default => null,
        };

        return is_array($values) ? new ProviderCredential($provider, $values) : null;
    }

    /**
     * @param array<string, string> $variables
     * @return array<string, string>|null
     */
    private function read(array $variables): ?array
    {
        $values = [];
        foreach ($variables as $key => $variable) {
            $value = getenv($variable);
            if (!is_string($value) || trim($value) === '') {
                return null;
            }
            $values[$key] = trim($value);
        }

        return $values;
    }
}
