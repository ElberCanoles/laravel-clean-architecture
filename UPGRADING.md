# Upgrade Guide

This guide covers changes that may require action when upgrading. Remember that
**stubs are templates**: changes to generated code never touch files you already
generated — they apply when you generate new artifacts or re-run a generator
with `--force`. If you published custom stubs, re-publish after upgrading:

```bash
php artisan vendor:publish --tag=clean-architecture-stubs --force
```

## To 1.5

### Requirements

- Minimum Laravel is now **11.17** (`Str::uuid7()`, emitted by generated code, does not exist before it).
- **Laravel 11 support is deprecated** and will be removed in v2.0 — Laravel 11 is end-of-life and carries unpatched security advisories. Move to Laravel 12 or 13.

### Command behavior

- **Exit codes are now meaningful.** Invalid input exits with code `2` and a clean error (previously an uncaught stack trace); a skipped write (file exists without `--force`) or a failed write exits with code `1` (previously `0`). Scripts that relied on generators always exiting `0` must check for these codes.
- **Names are normalized**: `clean:entity billing invoice` now generates `Billing\…\Invoice` instead of erroring. PHP reserved words (`List`, `Class`, …) are now rejected.
- **Suffixes are deduplicated**: `clean:domain-event Billing InvoicePaidEvent` now produces `InvoicePaidEvent`, not `InvoicePaidEventEvent`.
- `clean:scaffold --force` now **overwrites** the existing migration instead of stacking a duplicate.

### Generated code (new generations / `--force` re-runs only)

- Create commands now carry `public string $id` in addition to `array $data`; the id is generated in the controller and returned in the `201` response with a `Location` header.
- `WriteRepository` interfaces gained `ofId(string $id): ?Entity`; update/delete handlers load and guard through it and throw a generated `{Entity}NotFound` (rendered as HTTP 404). If you re-generate a repository interface with `--force`, add `ofId()` to any hand-written implementation of it.
- Generated `update()`/`destroy()` controller methods return `204 No Content`.
- Specifications now extend `CleanArchitecture\Support\CompositeSpecification` — composition (`and`/`or`/`not`) works across different specification classes. Hand-written specs can opt in by extending it.
- Everything generated declares `strict_types=1`, and the architecture tests grew from 7 to 9 rules ("Application must not use `Illuminate\*`", "code declares strict types"). Existing contexts with hand-written code may need `declare(strict_types=1)` added to pass the new rule after re-generating their arch test.

### New behavior you may want to disable

- Uncaught `\DomainException`s now render as JSON (`422`, or the status from `ProvidesHttpStatus`) on requests that expect JSON. Set `render_domain_exceptions => false` in `config/clean-architecture.php` to keep your own handling.

## To 1.3

- Entity constructors became `private` — construct through `create()` / `fromPersistence()`.
- `ReadRepository::findAll()` returns `PaginatedResult` instead of `array`; generated list handlers and controllers changed accordingly.
- Generated controller `index()` now receives `Request`.

## To 1.2.1

- The default `namespace_prefix` changed from `App` to `Src`. **Breaking if you had not published the config**: newly generated code moved from the `App\` namespace to `Src\`. To keep the old default, publish the config and set `'namespace_prefix' => 'App'`.
