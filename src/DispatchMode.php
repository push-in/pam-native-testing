<?php

declare(strict_types=1);

namespace Pam\Native\Testing;

enum DispatchMode: int
{
    case Immediate = 1;
    case Deferred = 2;
}
