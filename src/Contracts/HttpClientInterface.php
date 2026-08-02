<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Contracts;

use Refatbd\GameAccountLookup\Http\HttpResponse;

interface HttpClientInterface
{
    /**
     * @param array<string, string> $headers
     */
    public function get(string $url, array $headers = []): HttpResponse;

    /**
     * @param array<string, scalar|null> $form
     * @param array<string, string> $headers
     */
    public function postForm(string $url, array $form, array $headers = []): HttpResponse;

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    public function postJson(string $url, array $payload, array $headers = []): HttpResponse;
}
