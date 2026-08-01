<?php

declare(strict_types=1);

namespace Pam\Native\Testing;

use Pam\Native\Modules\NativeModules;

final class NativeTestHarness
{
    private function __construct()
    {
    }

    public static function install(?FakeNativeModuleTransport $transport = null): FakeNativeModuleTransport
    {
        $transport ??= new FakeNativeModuleTransport();
        NativeModules::useTransport($transport);

        return $transport;
    }

    public static function uninstall(): void
    {
        NativeModules::useTransport(null);
    }
}
