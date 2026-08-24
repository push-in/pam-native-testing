<?php

declare(strict_types=1);

namespace Pam\Native\Testing\Visual;

use InvalidArgumentException;

final readonly class PixelBuffer
{
    /** @param list<int> $rgba */
    public function __construct(
        public int $width,
        public int $height,
        public array $rgba,
    ) {
        if ($width < 1 || $height < 1 || count($rgba) !== $width * $height * 4) {
            throw new InvalidArgumentException('Pixel buffer dimensions do not match RGBA data.');
        }
        foreach ($rgba as $channel) {
            if ($channel < 0 || $channel > 255) {
                throw new InvalidArgumentException('Pixel channels must be between 0 and 255.');
            }
        }
    }

    /** @return array{red: float, green: float, blue: float, alpha: float, luminance: float} */
    public function averages(): array
    {
        $count = $this->width * $this->height;
        $red = $green = $blue = $alpha = 0;
        for ($offset = 0; $offset < count($this->rgba); $offset += 4) {
            $red += $this->rgba[$offset];
            $green += $this->rgba[$offset + 1];
            $blue += $this->rgba[$offset + 2];
            $alpha += $this->rgba[$offset + 3];
        }
        $r = $red / $count;
        $g = $green / $count;
        $b = $blue / $count;
        return ['red' => $r, 'green' => $g, 'blue' => $b, 'alpha' => $alpha / $count, 'luminance' => (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255];
    }
}
