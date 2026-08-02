<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Tests\Fakes;

use Refatbd\GameAccountLookup\Contracts\HttpClientInterface;
use Refatbd\GameAccountLookup\Http\HttpResponse;
use RuntimeException;

final class FakeHttpClient implements HttpClientInterface
{
    /** @var list<HttpResponse> */
    private array $responses;

    /** @var list<array<string, mixed>> */
    public array $requests = [];

    public int $cookieResets = 0;

    public function __construct(HttpResponse ...$responses)
    {
        $this->responses = $responses;
    }

    public function get(string $url, array $headers = []): HttpResponse
    {
        $this->requests[] = compact('url', 'headers') + ['method' => 'GET'];

        return $this->next();
    }

    public function postForm(string $url, array $form, array $headers = []): HttpResponse
    {
        $this->requests[] = compact('url', 'form', 'headers') + ['method' => 'POST_FORM'];

        return $this->next();
    }

    public function postJson(string $url, array $payload, array $headers = []): HttpResponse
    {
        $this->requests[] = compact('url', 'payload', 'headers') + ['method' => 'POST_JSON'];

        return $this->next();
    }

    public function clearCookies(): void
    {
        $this->cookieResets++;
    }

    private function next(): HttpResponse
    {
        $response = array_shift($this->responses);

        if (!$response instanceof HttpResponse) {
            throw new RuntimeException('Fake HTTP response queue is empty.');
        }

        return $response;
    }
}
