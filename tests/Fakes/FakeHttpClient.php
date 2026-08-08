<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Tests\Fakes;

use Refatbd\GameAccountLookup\Contracts\SessionAwareHttpClientInterface;
use Refatbd\GameAccountLookup\Http\HttpResponse;
use RuntimeException;

final class FakeHttpClient implements SessionAwareHttpClientInterface
{
    /** @var list<HttpResponse> */
    private array $responses;

    /** @var list<array<string, mixed>> */
    public array $requests = [];

    public int $cookieResets = 0;

    public bool $warmSession = false;

    public int $sessionForgets = 0;

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

    public function hasWarmSession(string $url): bool
    {
        return $this->warmSession;
    }

    public function markSessionWarm(string $url): void
    {
        $this->warmSession = true;
    }

    public function forgetSession(string $url): void
    {
        $this->warmSession = false;
        $this->sessionForgets++;
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
