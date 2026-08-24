<?php

declare(strict_types=1);

namespace Pam\Native\Testing\Visual;

final readonly class VisualDiff
{
    public function __construct(
        public int $changedPixels,
        public int $totalPixels,
        public float $meanError,
        public float $maximumError,
        public bool $accepted,
    ) {}

    public function changedRatio(): float
    {
        return $this->totalPixels === 0 ? 0.0 : $this->changedPixels / $this->totalPixels;
    }
}
