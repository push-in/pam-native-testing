<?php

declare(strict_types=1);

namespace Pam\Native\Testing\Visual;

use InvalidArgumentException;

final readonly class GoldenComparator
{
    public function __construct(
        private float $channelTolerance = 0.01,
        private float $changedPixelTolerance = 0.001,
    ) {
        if ($channelTolerance < 0.0 || $channelTolerance > 1.0 || $changedPixelTolerance < 0.0 || $changedPixelTolerance > 1.0) {
            throw new InvalidArgumentException('Visual tolerances must be between zero and one.');
        }
    }

    public function compare(PixelBuffer $expected, PixelBuffer $actual): VisualDiff
    {
        if ($expected->width !== $actual->width || $expected->height !== $actual->height) {
            throw new InvalidArgumentException('Golden and actual images must have identical dimensions.');
        }
        $changed = 0;
        $sum = 0.0;
        $maximum = 0.0;
        $pixels = $expected->width * $expected->height;
        for ($offset = 0; $offset < count($expected->rgba); $offset += 4) {
            $pixelMaximum = 0.0;
            for ($channel = 0; $channel < 4; $channel++) {
                $error = abs($expected->rgba[$offset + $channel] - $actual->rgba[$offset + $channel]) / 255;
                $sum += $error;
                $maximum = max($maximum, $error);
                $pixelMaximum = max($pixelMaximum, $error);
            }
            if ($pixelMaximum > $this->channelTolerance) {
                $changed++;
            }
        }
        $mean = $pixels === 0 ? 0.0 : $sum / ($pixels * 4);
        return new VisualDiff($changed, $pixels, $mean, $maximum, ($changed / $pixels) <= $this->changedPixelTolerance);
    }
}
