# PAM Native Testing

Deterministic, strict bridge testing for PAM Native applications and plugins.

```bash
composer require --dev pushinbr/pam-native-testing
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
