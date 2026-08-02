<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup;

final class ResultCode
{
    public const SUCCESS = 'SUCCESS';
    public const CACHED = 'CACHED';
    public const INVALID_PLAYER = 'INVALID_PLAYER';
    public const GAME_NOT_FOUND = 'GAME_NOT_FOUND';
    public const ZONE_REQUIRED = 'ZONE_REQUIRED';
    public const ZONE_INVALID = 'ZONE_INVALID';
    public const PROVIDER_NOT_CONFIGURED = 'PROVIDER_NOT_CONFIGURED';
    public const MAINTENANCE_REQUIRED = 'MAINTENANCE_REQUIRED';
    public const PROVIDER_RESTRICTED = 'PROVIDER_RESTRICTED';
    public const PROVIDER_MAINTENANCE = 'PROVIDER_MAINTENANCE';
    public const RATE_LIMITED = 'RATE_LIMITED';
    public const NETWORK_ERROR = 'NETWORK_ERROR';
    public const INVALID_RESPONSE = 'INVALID_RESPONSE';
    public const ALL_PROVIDERS_FAILED = 'ALL_PROVIDERS_FAILED';

    private function __construct()
    {
    }
}
