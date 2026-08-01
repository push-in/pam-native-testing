<?php

declare(strict_types=1);

namespace Pam\Native\Testing;

use Pam\Native\ModuleResultStatus;

final readonly class StubbedModuleResponse
{
    public function __construct(
        public ModuleResultStatus $status,
        public string $payload,
        public DispatchMode $dispatchMode = DispatchMode::Immediate,
    ) {
    }
}
