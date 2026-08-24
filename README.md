# PAM Native Testing

## Start here

This is a Composer extension for PAM Native. Install the PAM Runtime, create a native project, and then add this package through PAM’s verified Composer toolchain:

```bash
curl --proto '=https' --proto-redir '=https' --tlsv1.2 \
    --connect-timeout 15 --max-time 60 --max-filesize 1048576 -fsSL \
    https://github.com/push-in/pam/releases/latest/download/install.sh | sh

pam init my-app --template native
cd my-app
pam composer require pushinbr/pam-native-testing
pam doctor --fix
```


Deterministic, strict bridge testing for PAM Native applications and plugins.

```bash
pam add testing
pam doctor
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

`pam add testing` resolves the official compatible package, performs a non-mutating Composer preflight, updates the normal `composer.json` and `composer.lock`, refreshes generated native integration when required, and leaves the project ready for `pam doctor` validation.

Use `pam packages` to inspect availability and `pam remove testing` to uninstall the capability safely. Direct Composer commands are an advanced interoperability path; PAM is the supported application workflow.

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
