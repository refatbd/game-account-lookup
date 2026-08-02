<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Providers;

use Refatbd\GameAccountLookup\Contracts\ProviderInterface;
use Refatbd\GameAccountLookup\DTO\LookupResult;
use Refatbd\GameAccountLookup\ResultCode;
use Throwable;

final class MidasbuyBrowserProvider implements ProviderInterface
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly bool $debug = false,
    ) {
    }

    public function key(): string
    {
        return 'midasbuy_browser';
    }

    public function supports(array $game): bool
    {
        $config = $game['providers'][$this->key()] ?? null;

        return is_array($config) && ($config['enabled'] ?? true) === true;
    }

    public function lookup(array $game, string $playerId, ?string $zoneId = null): LookupResult
    {
        $config = (array) ($game['providers'][$this->key()] ?? []);
        $script = (string) ($config['script'] ?? ($this->projectRoot . '/scripts/midasbuy-browser-lookup.mjs'));
        $node = (string) ($config['node'] ?? getenv('GAME_LOOKUP_NODE_PATH') ?: 'node');
        $timeout = max(30, (int) ($config['timeout'] ?? 90));

        if (!is_file($script) || !function_exists('proc_open')) {
            return LookupResult::failure(
                ResultCode::PROVIDER_NOT_CONFIGURED,
                'Browser-assisted Midasbuy lookup requires Node.js and the bundled browser helper.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
                ['script' => $script, 'proc_open_available' => function_exists('proc_open')],
            );
        }

        $payload = [
            'player_id' => $playerId,
            'page_url' => (string) ($config['pageUrl'] ?? 'https://www.midasbuy.com/midasbuy/bd/buy/pubgm'),
            'timeout_ms' => $timeout * 1000,
            'profile_dir' => (string) ($config['profileDir'] ?? ($this->projectRoot . '/template/storage/midasbuy-browser-profile')),
            'debug_port' => (int) ($config['debugPort'] ?? 9224),
        ];

        try {
            $result = $this->run([$node, $script], json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '{}', $timeout + 30);
        } catch (Throwable $exception) {
            return LookupResult::failure(
                ResultCode::PROVIDER_NOT_CONFIGURED,
                'Browser-assisted Midasbuy lookup is unavailable in this hosting environment.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
                [
                    'browser_assisted' => true,
                    'runtime_available' => false,
                    'exception_type' => $exception::class,
                    'exception_message' => $this->debug ? $exception->getMessage() : null,
                ],
            );
        }
        $data = json_decode(trim($result['stdout']), true);

        if (!is_array($data)) {
            return LookupResult::failure(
                ResultCode::INVALID_RESPONSE,
                'Browser-assisted Midasbuy helper returned an invalid response.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
                ['exit_code' => $result['exit_code'], 'timed_out' => $result['timed_out'], 'stderr' => $this->debug ? trim($result['stderr']) : null],
            );
        }

        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $meta['exit_code'] = $result['exit_code'];
        $meta['browser_assisted'] = true;
        $nickname = trim((string) ($data['nickname'] ?? ''));

        if (($data['ok'] ?? false) === true && $nickname !== '') {
            $country = strtoupper(trim((string) ($data['country'] ?? $meta['country'] ?? '')));

            return LookupResult::success(
                (string) ($game['code'] ?? ''),
                $playerId,
                $nickname,
                $this->key(),
                $zoneId,
                null,
                $meta,
                $country !== '' ? $country : null,
            );
        }

        $code = (string) ($data['code'] ?? ResultCode::NETWORK_ERROR);
        if (!in_array($code, [ResultCode::INVALID_PLAYER, ResultCode::PROVIDER_RESTRICTED, ResultCode::PROVIDER_NOT_CONFIGURED, ResultCode::NETWORK_ERROR, ResultCode::INVALID_RESPONSE], true)) {
            $code = ResultCode::NETWORK_ERROR;
        }

        return LookupResult::failure(
            $code,
            trim((string) ($data['message'] ?? 'Browser-assisted Midasbuy lookup failed.')),
            $game['code'] ?? null,
            $playerId,
            $zoneId,
            $this->key(),
            $meta,
        );
    }

    /** @param list<string> $command @return array{stdout: string, stderr: string, exit_code: int, timed_out: bool} */
    private function run(array $command, string $stdin, int $timeout): array
    {
        $pipes = [];
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $this->projectRoot);
        if (!is_resource($process)) {
            return ['stdout' => '', 'stderr' => 'Could not start Node.js.', 'exit_code' => -1, 'timed_out' => false];
        }

        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stdout = '';
        $stderr = '';
        $deadline = microtime(true) + $timeout;
        $timedOut = false;
        $exitCode = -1;

        do {
            $stdout .= stream_get_contents($pipes[1]) ?: '';
            $stderr .= stream_get_contents($pipes[2]) ?: '';
            $status = proc_get_status($process);
            if (!$status['running']) {
                $exitCode = (int) $status['exitcode'];
                break;
            }
            if (microtime(true) >= $deadline) {
                $timedOut = true;
                proc_terminate($process);
                break;
            }
            usleep(50_000);
        } while (true);

        $stdout .= stream_get_contents($pipes[1]) ?: '';
        $stderr .= stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $closed = proc_close($process);
        if ($exitCode < 0 && $closed >= 0) $exitCode = $closed;

        return ['stdout' => $stdout, 'stderr' => $stderr, 'exit_code' => $exitCode, 'timed_out' => $timedOut];
    }
}
