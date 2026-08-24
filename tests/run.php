<?php

declare(strict_types=1);

$autoload = dirname(__DIR__).'/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    $roots = [
        'Pam\\Native\\Testing\\' => dirname(__DIR__).'/src/',
        'Pam\\Native\\' => dirname(__DIR__, 2).'/../pam-native/packages/native/src/',
    ];
    spl_autoload_register(static function (string $class) use ($roots): void {
        foreach ($roots as $prefix => $root) {
            if (str_starts_with($class, $prefix)) {
                $path = $root.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
                if (is_file($path)) {
                    require $path;
                }
                return;
            }
        }
    });
}

use Pam\Native\Internal\Wire;
use Pam\Native\Modules\NativeModuleResult;
use Pam\Native\Modules\NativeModules;
use Pam\Native\Testing\DispatchMode;
use Pam\Native\Testing\NativeTestHarness;
use Pam\Native\Testing\Visual\GoldenComparator;
use Pam\Native\Testing\Visual\PixelBuffer;
use Pam\Native\Testing\Visual\ScreenHealth;

$tests = [];
$test = static function (string $name, Closure $callback) use (&$tests): void { $tests[$name] = $callback; };
$expect = static function (bool $condition, string $message = 'Expectation failed'): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$test('dispatches typed successful responses immediately', static function () use ($expect): void {
    $fake = NativeTestHarness::install();
    $fake->succeed('auth.session', 'current', ['identifier' => 'user-1', 'state' => 2]);
    $result = null;
    NativeModules::call('auth.session', 'current', ['refresh' => true], static function (NativeModuleResult $value) use (&$result): void {
        $result = $value;
    });
    if (!$result instanceof NativeModuleResult) {
        throw new RuntimeException('Native result was not delivered.');
    }
    $expect($result->succeeded());
    $expect($result->values() === ['identifier' => 'user-1', 'state' => 2]);
    $call = $fake->lastCall();
    if ($call === null) {
        throw new RuntimeException('Native call was not recorded.');
    }
    $expect(Wire::decodeMap($call->payload) === ['refresh' => true]);
    $fake->assertCalled('auth.session', 'current');
    $fake->assertSatisfied();
    NativeTestHarness::uninstall();
});

$test('controls deferred completion order', static function () use ($expect): void {
    $fake = NativeTestHarness::install();
    $fake->succeed('sync.engine', 'push', ['accepted' => true], DispatchMode::Deferred);
    $completed = false;
    NativeModules::call('sync.engine', 'push', [], static function () use (&$completed): void { $completed = true; });
    $expect(!$completed, 'Deferred response completed early.');
    $expect($fake->flushOne(), 'Expected one pending response.');
    $expect($completed, 'Deferred response did not complete.');
    $expect(!$fake->flushOne(), 'Unexpected second pending response.');
    $fake->assertSatisfied();
    NativeTestHarness::uninstall();
});

$test('normalizes failures and rejects unstubbed calls', static function () use ($expect): void {
    $fake = NativeTestHarness::install();
    $fake->fail('payments.sheet', 'present', 'Merchant is not configured.');
    $message = null;
    NativeModules::call('payments.sheet', 'present', [], static function (NativeModuleResult $result) use (&$message): void {
        $message = $result->message();
    });
    $expect($message === 'Merchant is not configured.');
    try {
        NativeModules::call('payments.sheet', 'present', [], static fn (): null => null);
        $expect(false, 'Unstubbed call unexpectedly succeeded.');
    } catch (LogicException $exception) {
        $expect(str_contains($exception->getMessage(), 'Unexpected native module call'));
    }
    NativeTestHarness::uninstall();
});

$test('detects black frames and certifies golden pixels deterministically', static function () use ($expect): void {
    $black = new PixelBuffer(1, 1, [0, 0, 0, 255]);
    $actual = new PixelBuffer(2, 1, [255, 0, 0, 255, 0, 255, 0, 255]);
    $expected = new PixelBuffer(2, 1, [255, 0, 0, 255, 0, 254, 0, 255]);
    $health = (new ScreenHealth())->inspect($black);
    $diff = (new GoldenComparator(channelTolerance: 0.01))->compare($expected, $actual);
    $expect(!$health['healthy'] && $health['reason'] === 'black-frame');
    $expect($diff->accepted && $diff->changedPixels === 0 && $diff->maximumError > 0.0);
});

$failures = 0;
foreach ($tests as $name => $callback) {
    try {
        $callback();
        fwrite(STDOUT, "PASS {$name}\n");
    } catch (Throwable $throwable) {
        ++$failures;
        NativeTestHarness::uninstall();
        fwrite(STDERR, "FAIL {$name}: {$throwable->getMessage()}\n");
    }
}
fwrite(STDOUT, sprintf("%d tests, %d failures\n", count($tests), $failures));
exit($failures === 0 ? 0 : 1);
