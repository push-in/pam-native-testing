<?php

declare(strict_types=1);

namespace Pam\Native\Testing;

use Closure;
use LogicException;
use Pam\Native\Internal\Wire;
use Pam\Native\ModuleResultStatus;
use Pam\Native\Modules\NativeModuleTransport;

final class FakeNativeModuleTransport implements NativeModuleTransport
{
    /** @var array<string, list<StubbedModuleResponse>> */
    private array $responses = [];

    /** @var list<array{response: StubbedModuleResponse, complete: Closure}> */
    private array $pending = [];

    /** @var list<RecordedModuleCall> */
    private array $calls = [];

    /** @param array<string, string|int|float|bool> $values */
    public function succeed(
        string $module,
        string $method,
        array $values = [],
        DispatchMode $dispatchMode = DispatchMode::Immediate,
    ): self {
        return $this->respond($module, $method, new StubbedModuleResponse(
            ModuleResultStatus::Success,
            Wire::map($values),
            $dispatchMode,
        ));
    }

    public function fail(
        string $module,
        string $method,
        string $message,
        DispatchMode $dispatchMode = DispatchMode::Immediate,
    ): self {
        return $this->respond($module, $method, new StubbedModuleResponse(
            ModuleResultStatus::Failure,
            $message,
            $dispatchMode,
        ));
    }

    public function respond(string $module, string $method, StubbedModuleResponse $response): self
    {
        $this->responses[$this->key($module, $method)][] = $response;

        return $this;
    }

    public function invoke(
        int $requestId,
        string $module,
        string $method,
        string $payload,
        Closure $complete,
    ): void {
        $call = new RecordedModuleCall($requestId, $module, $method, $payload);
        $this->calls[] = $call;
        $key = $this->key($module, $method);
        $queue = $this->responses[$key] ?? [];
        $response = array_shift($queue);
        if (!$response instanceof StubbedModuleResponse) {
            throw new LogicException("Unexpected native module call {$module}.{$method}.");
        }
        if ($queue === []) {
            unset($this->responses[$key]);
        } else {
            $this->responses[$key] = $queue;
        }
        if ($response->dispatchMode === DispatchMode::Deferred) {
            $this->pending[] = ['response' => $response, 'complete' => $complete];
            return;
        }
        $complete($response->status, $response->payload);
    }

    public function flushOne(): bool
    {
        $pending = array_shift($this->pending);
        if ($pending === null) {
            return false;
        }
        $pending['complete']($pending['response']->status, $pending['response']->payload);
        return true;
    }

    public function flush(): void
    {
        while ($this->flushOne()) {
        }
    }

    /** @return list<RecordedModuleCall> */
    public function calls(): array
    {
        return $this->calls;
    }

    public function lastCall(): ?RecordedModuleCall
    {
        $key = array_key_last($this->calls);
        return $key === null ? null : $this->calls[$key];
    }

    public function assertCalled(string $module, string $method, int $times = 1): void
    {
        $actual = count(array_filter(
            $this->calls,
            static fn (RecordedModuleCall $call): bool => $call->module === $module && $call->method === $method,
        ));
        if ($actual !== $times) {
            throw new LogicException("Expected {$module}.{$method} {$times} time(s), observed {$actual}.");
        }
    }

    public function assertSatisfied(): void
    {
        $remaining = array_sum(array_map(count(...), $this->responses));
        if ($remaining !== 0 || $this->pending !== []) {
            throw new LogicException(sprintf(
                'Fake transport is not satisfied: %d unused response(s), %d pending completion(s).',
                $remaining,
                count($this->pending),
            ));
        }
    }

    public function reset(): void
    {
        $this->responses = [];
        $this->pending = [];
        $this->calls = [];
    }

    private function key(string $module, string $method): string
    {
        return $module."\0".$method;
    }
}
