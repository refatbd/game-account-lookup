<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Credentials;

interface CredentialProviderInterface
{
    public function forProvider(string $provider): ?ProviderCredential;
}
