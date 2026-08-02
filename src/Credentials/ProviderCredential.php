<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Credentials;

use InvalidArgumentException;

/** Server-side credential group for one provider. */
final readonly class ProviderCredential
{
    /** @param array<string, string> $values */
    public function __construct(
        public string $provider,
        private array $values,
    ) {
        if ($provider === '') {
            throw new InvalidArgumentException('Credential provider cannot be empty.');
        }

        foreach ($values as $key => $value) {
            if ($key === '' || $value === '') {
                throw new InvalidArgumentException('Credential names and values cannot be empty.');
            }
        }
    }

    public function value(string $key): ?string
    {
        return $this->values[$key] ?? null;
    }
}
