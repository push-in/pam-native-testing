<!-- pam:product-page:start -->
<div align="center">

# PAM Native Testing

**Deterministic native contracts without flaky device-only feedback loops.**

Fake bridge behavior, assert emitted commands, compare golden images, and fail fast on black or transparent screens.

[![Latest version](https://img.shields.io/packagist/v/pushinbr/pam-native-testing?style=flat-square&label=stable)](https://packagist.org/packages/pushinbr/pam-native-testing)
[![CI](https://img.shields.io/github/actions/workflow/status/push-in/pam-native-testing/ci.yml?branch=main&style=flat-square&label=CI)](https://github.com/push-in/pam-native-testing/actions)
![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?style=flat-square&logo=php&logoColor=white)
![Android](https://img.shields.io/badge/Android-API%2026%2B-3DDC84?style=flat-square&logo=android&logoColor=white)
![iOS](https://img.shields.io/badge/iOS-15%2B-000000?style=flat-square&logo=apple&logoColor=white)

**[Documentation](https://push-in.github.io/pam-docs/native/overview/) · [Quick start](#quick-start) · [What you can build](#what-you-can-build) · [PAM ecosystem](https://push-in.github.io/pam-docs/ecosystem/) · [Issues](https://github.com/push-in/pam-native-testing/issues)**

</div>

---

## Why PAM Native Testing

Fake bridge behavior, assert emitted commands, compare golden images, and fail fast on black or transparent screens. The public API is strictly typed for PHP 8.5; expensive or frame-sensitive work stays in Rust or the platform SDK instead of crossing the application boundary every frame.

| | |
| --- | --- |
| **Best for** | A focused capability you can add to any PAM Native application |
| **Native path** | Bridge fakes · Golden comparator · Screen sentinels |
| **Application model** | Composer package + generated native integration |
| **Design rule** | Independent module; no feed, vertical, or application template bundled |

## What you can build

- Unit tests for components and plugins
- Golden visual regression suites
- CI certification of native event and bridge contracts

## Quick start

Already have a PAM Native project? Add only this capability:

```bash
pam composer require pushinbr/pam-native-testing
pam doctor --fix
```

New to PAM? Follow the **[five-minute PAM Native setup](https://push-in.github.io/pam-docs/native/overview/)** once, then return here. Your application stays a normal Composer project with a committed lockfile.
<!-- pam:product-page:end -->

## See it in action

Deterministic, strict bridge testing for PAM Native applications and plugins.

```bash
pam composer require pushinbr/pam-native-testing
pam doctor --fix
```

```php
$native = NativeTestHarness::install();
$native->succeed('auth.session', 'current', [
    'identifier' => 'user-1',
    'state' => 2,
]);

// Exercise production PHP code.

$native->assertCalled('auth.session', 'current');
$native->assertSatisfied();
NativeTestHarness::uninstall();
```

Responses can be immediate or deferred. Unstubbed calls fail immediately, and
`assertSatisfied()` detects unused responses and unflushed completions.

Visual certification is deterministic and framework-independent: provide the
RGBA pixels captured by Android or iOS, reject black/transparent launch frames,
and compare the result with a golden image using explicit channel and changed-pixel tolerances.

```php
$health = (new ScreenHealth())->inspect($capturedFrame);
$diff = (new GoldenComparator(channelTolerance: 0.01))->compare($golden, $capturedFrame);

if (!$health['healthy'] || !$diff->accepted) {
    throw new RuntimeException('Native screen failed visual certification.');
}
```

## What installation does

`pam composer require pushinbr/pam-native-testing` installs the package through the project's normal `composer.json` and `composer.lock`. Run `pam doctor --fix` afterward to validate the environment and regenerate native integration when required.

Use `pam packages` to inspect direct installed Composer dependencies and `pam composer remove pushinbr/pam-native-testing` to uninstall the capability.

## API guide

| API | Responsibility |
| --- | --- |
| `NativeTestHarness` | Install and remove the fake native-module transport. |
| `FakeNativeModuleTransport` | Stub success/failure, record calls, defer responses, and assert completion. |
| `DispatchMode` | Choose immediate or deferred callback execution. |
| `RecordedModuleCall` | Inspect exact module, method, and payload calls. |
| `StubbedModuleResponse` | Describe deterministic native results. |
| `PixelBuffer` | Validate a platform-neutral RGBA screenshot contract. |
| `ScreenHealth` | Detect black and transparent startup frames. |
| `GoldenComparator` / `VisualDiff` | Certify screenshots with measurable tolerances. |

All coded states, kinds, and variants are sequential integer-backed enums. Use enum cases in application code; do not depend on raw wire numbers.

## Production checklist

- Uninstall the harness during test teardown.
- Fail every unstubbed native call so tests cannot pass accidentally.
- Call `assertSatisfied()` to catch unused responses and pending completions.
- Run `pam doctor`, `pam test`, and a signed release build on every supported platform.
- Exercise denial, cancellation, backgrounding, process restart, and offline behavior before release.

## Troubleshooting

- **A test hangs:** flush deferred responses or change the dispatch mode.
- **An assertion sees extra calls:** reset or reinstall the harness per test.
- **Production transport leaks between tests:** enforce teardown even after failures.
- **Native integration is stale:** run `pam doctor --fix`, rebuild the native host, and inspect the first reported diagnostic.

## Compatibility and support

This package targets PAM Native `0.8.x`, PHP 8.5, Android API 26+, and iOS 15+ unless a platform-specific section above states a stricter requirement. Platform SDKs, credentials, entitlements, physical hardware, and store configuration remain application responsibilities.

- [PAM documentation](https://push-in.github.io/pam-docs/introduction/)
- [PAM Native overview](https://push-in.github.io/pam-docs/native/overview/)
- [Plugin and native capability model](https://push-in.github.io/pam-docs/native/plugins/)
- [Report an issue](https://github.com/push-in/pam-native-testing/issues)

Security vulnerabilities should be reported through the repository security policy or GitHub private vulnerability reporting, not a public issue.
