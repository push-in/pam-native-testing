<?php

declare(strict_types=1);

namespace Pam\Native\Testing\Visual;

final readonly class ScreenHealth
{
    /** @return array{healthy: bool, reason: string, luminance: float, alpha: float} */
    public function inspect(PixelBuffer $buffer): array
    {
        $averages = $buffer->averages();
        $healthy = $averages['alpha'] >= 250.0 && $averages['luminance'] >= 0.005;
        $reason = $healthy ? 'rendered' : ($averages['alpha'] < 250.0 ? 'transparent-frame' : 'black-frame');
        return ['healthy' => $healthy, 'reason' => $reason, 'luminance' => $averages['luminance'], 'alpha' => $averages['alpha']];
    }
}
