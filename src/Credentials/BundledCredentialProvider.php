<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Credentials;

/**
 * Bundled credentials for this single-owner private build.
 *
 * Keep all bundled rotating values in this class only. Never expose them in
 * API responses, logs, frontend code, fixtures, or documentation.
 */
final class BundledCredentialProvider implements CredentialProviderInterface
{
    /** @var array<string, string> */
    private const GARENA = [
        'cookie' => '_ga=GA1.1.2123120599.1674510784; _fbp=fb.1.1674510785537.363500115; _ga_7JZFJ14B0B=GS1.1.1674510784.1.1.1674510789.0.0.0; source=mb; region=MA; language=ar; _ga_TVZ1LG7BEB=GS1.1.1674930050.3.1.1674930171.0.0.0; datadome=6h5F5cx_GpbuNtAkftMpDjsbLcL3op_5W5Z-npxeT_qcEe_7pvil2EuJ6l~JlYDxEALeyvKTz3~LyC1opQgdP~7~UDJ0jYcP5p20IQlT3aBEIKDYLH~cqdfXnnR6FAL0; session_key=efwfzwesi9ui8drux4pmqix4cosane0y',
        'dataDomeClientId' => '6h5F5cx_GpbuNtAkftMpDjsbLcL3op_5W5Z-npxeT_qcEe_7pvil2EuJ6l~JlYDxEALeyvKTz3~LyC1opQgdP~7~UDJ0jYcP5p20IQlT3aBEIKDYLH~cqdfXnnR6FAL0',
    ];

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
            'garena' => self::GARENA,
            'midasbuy' => self::MIDASBUY,
            default => null,
        };

        return is_array($values) ? new ProviderCredential($provider, $values) : null;
    }
}
