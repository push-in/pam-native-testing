<?php

declare(strict_types=1);

namespace Pam\Native\Testing;

final readonly class RecordedModuleCall
{
    public function __construct(
        public int $requestId,
        public string $module,
        public string $method,
        public string $payload,
    ) {
    }
}
