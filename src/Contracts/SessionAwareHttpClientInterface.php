<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Contracts;

interface SessionAwareHttpClientInterface extends HttpClientInterface
{
    public function hasWarmSession(string $url): bool;

    public function markSessionWarm(string $url): void;

    public function forgetSession(string $url): void;
}
