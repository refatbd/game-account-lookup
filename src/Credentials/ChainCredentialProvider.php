<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Credentials;

final class ChainCredentialProvider implements CredentialProviderInterface
{
    /** @param list<CredentialProviderInterface> $providers */
    public function __construct(private readonly array $providers)
    {
    }

    public function forProvider(string $provider): ?ProviderCredential
    {
        foreach ($this->providers as $credentialProvider) {
            $credential = $credentialProvider->forProvider($provider);
            if ($credential !== null) {
                return $credential;
            }
        }

        return null;
    }
}
