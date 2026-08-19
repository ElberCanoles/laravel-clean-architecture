# Contributing

Thanks for considering a contribution! This document explains how to get set up
and what we expect from changes.

## Getting started

```bash
git clone https://github.com/ElberCanoles/laravel-clean-architecture.git
cd laravel-clean-architecture
composer install
```

## The quality bar

Every pull request must pass the same checks CI runs:

```bash
composer test      # Pest — 140+ tests, including generated-code execution
composer analyse   # PHPStan level 8
composer lint      # Laravel Pint (fix automatically with: composer fix)
```

CI additionally runs the full support matrix (Laravel 11–13 × PHP 8.2–8.5),
`--prefer-lowest`, Windows, and a coverage threshold — a green local run is
usually enough, but the matrix has the final word.

## Working on generators and stubs

The stubs in `stubs/` **are the product** — the code users get. When you touch
them, keep in mind:

- **Layer purity is enforced**: generated Domain code imports nothing from the
  framework, and generated Application code must never import `Illuminate\*`.
  The generated architecture tests (see `stubs/arch-test.stub`) and this
  package's own test suite will fail otherwise.
- Every stub must emit `declare(strict_types=1)` and valid PHP — the
  `GeneratorContractTest` lints everything the scaffold produces with `php -l`,
  and the migration is executed against SQLite.
- If you add a stub, reference it from a generator: the stub-integrity test
  fails on orphan stubs or on generators pointing at missing stubs.
- Single-file generators extend `SingleFileGenerator` and only declare
  `subPath()`, `stubName()`, `suffix()`, and `label()` — resist the urge to
  hand-roll a new `handle()`.

## Tests

- Integration tests live in `tests/Integration` and run each artisan command
  against a temp directory inside the Testbench skeleton.
- Pure logic (Support classes) is unit-tested in `tests/Unit` without booting
  Laravel.
- New behavior needs a test; bug fixes need a regression test that fails
  without the fix.

## Commits and pull requests

- Follow the existing commit style: `feat: …`, `fix: …`, `refactor: …`,
  `docs: …`, `ci: …` — lowercase, imperative.
- Add an entry to the `[Unreleased]` section of `CHANGELOG.md`
  ([Keep a Changelog](https://keepachangelog.com) format).
- If the change alters generated code or command behavior, update the README
  and, when it requires user action on upgrade, `UPGRADING.md`.
- Keep PRs focused — one concern per PR reviews faster.

## Reporting bugs and proposing features

Use the [issue templates](https://github.com/ElberCanoles/laravel-clean-architecture/issues/new/choose).
For questions and ideas, open a [Discussion](https://github.com/ElberCanoles/laravel-clean-architecture/discussions).

**Never report security issues publicly** — use
[private vulnerability reporting](https://github.com/ElberCanoles/laravel-clean-architecture/security/advisories/new)
as described in [SECURITY.md](SECURITY.md).

## Release process (maintainers)

1. Move the `[Unreleased]` entries under a new `[x.y.z] - YYYY-MM-DD` heading
   and update the compare links at the bottom of `CHANGELOG.md`.
2. Update `UPGRADING.md` if the release contains behavior changes.
3. Commit, tag (`git tag vX.Y.Z`), and push the tag — the release workflow
   publishes the GitHub release with the changelog section as notes.
