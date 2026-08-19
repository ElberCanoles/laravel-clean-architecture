# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com), and this project adheres to [Semantic Versioning](https://semver.org).

## [Unreleased]

### Added

- **Domain events dispatch after the transaction commits** — `DispatchesDomainEvents` now uses `DB::afterCommit()`: immediate outside a transaction, deferred until the outermost commit inside one, and discarded on rollback, so listeners never react to writes that never happened
- **Entities record a creation event** — `create()` records a generated `{Entity}CreatedEvent` (`clean:entity` generates the event class when missing, and never overwrites a customized one), `recordEvent()` is finally exercised, and entities gained identity-based `equals()`
- **Generated unit tests assert real behavior** — the creation event, that releasing clears events, and identity equality (replacing a test that could never fail)
- **`route:cache` support** — the generated context ServiceProvider skips runtime route registration when routes are cached, and route files register a per-context name prefix (`billing.invoices.show`) so `route()` names never collide across contexts
- **`PaginatedResult` grew `lastPage()` and `hasMorePages()`**, validates its arguments, and `meta()` now includes `last_page`

- **Community infrastructure** — `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md` (Contributor Covenant 2.1), issue form templates (bug report / feature request, with security and Discussions contact links), a pull request template, and weekly Dependabot updates for Composer and GitHub Actions
- **Automated releases** — pushing a `v*` tag now publishes the GitHub release with the matching `CHANGELOG.md` section as notes

### Changed

- **`query-handler.stub` and `list-query-handler.stub` merged** — they were byte-identical; both query flavors now render from `query-handler.stub` (if you had published and customized the removed one, move your changes into `query-handler.stub`)
- **`resource.stub` is honest with static analysis** — `@mixin` docblock pointing at the backing read model, and the misleading `created_at` example (read models carry no timestamps) is gone

## [1.5.0] - 2026-08-19

### Added

- **`clean:cache` / `clean:clear`** — cache discovered contexts, providers, and PSR-4 mappings into `bootstrap/cache/clean-architecture.php`, skipping all filesystem scans on cached production boots
- **`php artisan about` integration** — shows discovered contexts, provider count, id type, and cache state
- **Name normalization** — inputs are normalized to StudlyCase like Laravel's own generators (`clean:entity billing invoice` ⇒ `Billing\…\Invoice`), and suffixes are deduplicated (`clean:domain-event Billing InvoicePaidEvent` no longer produces `InvoicePaidEventEvent`)
- **`UPGRADING.md`** and a **`docs/architecture.md`** guide (the conceptual half of the old README), leaving a task-focused README with a quickstart that ends in working `curl` calls and a "what you still have to fill in" checklist
- **Generator contract test** — a single dataset-driven test asserts create/exists/`--force` semantics, that `--force` actually changes content, and PHP validity for all 13 single-file generators (replacing 28 near-identical tests)
- **Stub integrity test** — orphan stubs or generators referencing missing stubs now fail the suite
- **`composer test:mutate`** script (Pest mutation testing; requires a coverage driver)

- **`ofId()` on write repositories** — generated `WriteRepository` interfaces and Eloquent implementations can now load an aggregate for a state-changing operation; `Mapper::toEntity()` and `Entity::fromPersistence()` are finally reachable
- **Working update and delete flows** — `--crud=update` generates a real load-guard-save handler (previously a silent no-op that returned HTTP 200 without changing anything) and `--crud=delete` guards against missing aggregates; both throw a generated `{Entity}NotFound` domain exception (new `not-found-exception.stub`, auto-created when missing)
- **Create responses return the new id** — generated `store()` produces the id at the presentation edge, passes it through the command, and responds `201` with `{"id": …}` plus a `Location` header
- **Domain exception rendering** — uncaught `\DomainException`s render as JSON (`422` by default) on requests expecting JSON; exceptions implementing the new `CleanArchitecture\Support\ProvidesHttpStatus` interface choose their own status. Toggle with the new `render_domain_exceptions` config key
- **Composable specifications** — new `Specification` interface plus `CompositeSpecification`, `AndSpecification`, `OrSpecification`, and `NotSpecification` in `CleanArchitecture\Support`; the specification stub now extends the base class
- **`declare(strict_types=1)`** — in every package source file and every generated file (all 28 stubs)
- **Two new generated architecture rules** (9 total) — Application must not depend on `Illuminate\*`, and all context code must declare strict types
- **`--id-type` on `clean:controller`** — the controller now owns id generation, so the scaffold forwards the resolved strategy to it
- **Generated-code verification in the suite** — a custom `toBeValidPhp()` expectation lints every scaffolded file with `php -l`, the scaffolded migration is executed against SQLite, and the generated entity/exception classes are loaded and exercised
- **Laravel Pint** — `pint.json` (Laravel preset, house-style overrides) with `composer lint` / `composer fix` scripts, enforced in CI
- **Two-axis CI matrix** — Laravel 11/12/13 × PHP 8.2–8.5 with the Testbench mapping and the official support-table exclusions, plus `--prefer-lowest`, a Windows leg, and a coverage job with a minimum threshold
- **`SECURITY.md`** — vulnerabilities can now be reported privately through GitHub Security Advisories
- **Reserved-word validation** — PHP keywords and reserved type names (`List`, `Class`, `Enum`, `String`, …) are rejected as context/entity names instead of generating uncompilable PHP
- **`--force` description** — the flag is now documented in every command signature (`artisan help clean:*`)

### Changed

- **The generator internals were restructured** — the 11 copy-pasted generator classes collapsed into a declarative `SingleFileGenerator` base; the scaffold's duplicated marker-rewriting logic lives in one `MarkerBlockWriter`; every context path flows through a single `contextPath()` helper (with the previously missing config fallback)
- **`ModuleLoader` no longer re-requires `vendor/autoload.php`** — it asks Composer for its registered class loader (fixing fatals in monorepos and custom vendor dirs), memoizes its directory scan per request, and honours the `clean:cache` manifest
- **PHPStan level raised from 6 to 8** — arguments and options flow through typed accessors; an empty `--entity=`/`--crud=` is now explicitly treated as absent
- **Create commands now carry `public string $id`** — the id is generated in the controller (`Str::uuid7()`/`Str::ulid()`) instead of inside the Application handler, removing the `Illuminate\Support\Str` dependency from the Application layer entirely
- **Generated `update()`/`destroy()` respond `204 No Content`** — via `response()->noContent()` with `Response` return types, instead of empty JSON bodies
- **Specification stub no longer emits anonymous-class composition** — the old `and(self $other): static` implementation could not compose different specification classes and crashed with a `TypeError` on three-term chains
- **Dev tooling modernized** — Pest widened to `^3.8|^4.1|^5.0` (each CI leg resolves the best major for its PHP; removes the `setAccessible()` deprecation noise on PHP 8.5), and `phpunit.xml` now declares test suites, a coverage `<source>`, and `failOnDeprecation`

- **Commands return real exit codes** — validation errors print a clean console error and exit with code 2 (`INVALID`) instead of an uncaught stack trace; a skipped write (file exists without `--force`) or a failed write exits with code 1 (`FAILURE`); `clean:scaffold` propagates sub-command failures and reports "completed with warnings" instead of claiming success
- **`clean:scaffold --force` overwrites the existing migration in place** — previously it stacked a second `create_*_table` migration with a fresh timestamp, breaking `php artisan migrate` with a duplicate table error
- **Generated list endpoints are deterministic and bounded** — the read Eloquent repository stub adds `->orderBy('id')` (pagination without ORDER BY yields undefined row order), and generated controllers clamp `per_page` to 1–100 and `page` to ≥ 1
- **Minimum supported Laravel raised to 11.17** — `Str::uuid7()`, emitted by generated create handlers, was introduced in Laravel 11.17; earlier 11.x versions fail at runtime
- **`illuminate/console` is now an explicit dependency** — the generators extend `Illuminate\Console\Command`, previously an undeclared transitive dependency
- **`clean:query` description corrected** — it no longer claims a read model is generated (use `clean:read-model`)
- **`composer.json` declares `homepage` and `support` links** — issues, source, and security advisory URLs now render on Packagist

### Deprecated

- **Laravel 11 support** — Laravel 11 reached end of life in March 2026 and carries unpatched security advisories; support will be removed in v2.0. Use Laravel 12 or 13.

### Fixed

- **Sanitizer stub taught a self-defeating pattern** — the `...$data` spread now comes first, so uncommented normalization keys override the raw input instead of being silently overridden by it
- **Wiring could truncate user files** — a PCRE failure (`preg_replace_callback` returning `null`) during binding or route wiring now aborts with an error and leaves the file untouched, instead of writing an empty ServiceProvider or routes file
- **Suffix collision silently skipped bindings** — scaffolding `User` after `SuperUser` now wires the `User` bindings; the idempotency check is anchored to the namespace separator
- **Routes could reference an unimported controller** — when the `use Illuminate\Support\Facades\Route;` anchor is missing, the import is inserted after the last `use` statement, or the route falls back to the fully qualified class name (with a warning), so generated routes never reference a class they do not import
- **Write failures reported as success** — a failed `File::put()` (read-only directory, full disk) is now reported as an error instead of "created"

## [1.4.0] - 2026-08-05

### Added

- **ULID support** — generated models, migrations, and create handlers can now use ULIDs instead of UUIDs
- **`id_type` config option** — sets the project-wide identifier strategy (`uuid` by default, or `ulid`)
- **`--id-type` option** — per-command override accepted by `clean:scaffold`, `clean:model`, and `clean:command`; takes precedence over the config value and fails fast on any value other than `uuid` or `ulid`
- **`idTrait()` and `idFactoryCall()` helpers in `BaseGenerator`** — map the resolved strategy to `HasUuids`/`HasUlids` and to `Str::uuid7()`/`Str::ulid()`
- **Stale stub detection** — `clean:model` and `clean:scaffold` warn when a published custom stub lacks the `{{IdTrait}}` / `{{idType}}` placeholder, so `--id-type` is never dropped silently; re-publish with `php artisan vendor:publish --tag=clean-architecture-stubs --force`

### Changed

- **`model.stub` uses `{{IdTrait}}`** — the Eloquent concern is injected by the generator instead of being hardcoded to `HasUuids`
- **`migration.stub` uses `{{idType}}`** — the primary key column is now `$table->{{idType}}('id')->primary()`
- **`clean:scaffold` resolves the identifier strategy once** — the resolved value is forwarded to `clean:model` and `clean:command`, so every file generated for an entity shares the same strategy

Defaults are unchanged: without `id_type` or `--id-type`, generators keep emitting `HasUuids`, `$table->uuid('id')`, and `Str::uuid7()`. Domain entities are unaffected — they still take a plain `string $id`.

## [1.3.0] - 2026-04-12

### Added

- **`HasDomainEvents` interface** — formal contract (`CleanArchitecture\Support\HasDomainEvents`) for entities that record domain events, replacing duck typing via `method_exists()`
- **`PaginatedResult` DTO** — generic `CleanArchitecture\Support\PaginatedResult<T>` for paginated read repository results, with `meta()` helper returning `total`, `page`, and `per_page`
- **Migration generation** — `clean:scaffold` now generates a database migration with UUID primary key and timestamps; warns if migration already exists
- **`fromPersistence()` factory method** — entities now have a dedicated static factory for reconstituting from database, keeping the `create()` method for new instances
- **`migration.stub`** — new stub for scaffold-generated database migrations

### Changed

- **Entity constructor is now `private`** — forces creation through `create()` or `fromPersistence()` factory methods, preventing invalid state
- **Entity `recordEvent()` is now `private`** — event recording is an internal concern, not part of the public API
- **Sanitizer passes through data by default** — generated sanitizers use `...$data` spread to prevent silent data loss
- **Read repository `findAll()` returns `PaginatedResult`** — replaces raw `array` return, providing items + pagination metadata
- **Read Eloquent repository uses `PaginatedResult`** — includes count query and `forPage()` pagination with proper DTO construction
- **List query handler returns `PaginatedResult`** — instead of `array`, matching the updated read repository contract
- **Controller `index()` receives `Request` parameter** — passes `$request->query('page')` and `$request->query('per_page')` to list query, making pagination functional from the API
- **Controller `index()` returns pagination metadata** — uses `->additional(['meta' => $result->meta()])` on the resource collection
- **Create handler uses `Str::uuid7()`** — time-ordered UUIDs replace `Str::uuid()->toString()` for better database indexing
- **Mapper uses `fromPersistence()`** — `toEntity()` calls `Entity::fromPersistence()` instead of `new Entity()` for proper reconstitution semantics
- **`DispatchesDomainEvents` checks `HasDomainEvents` interface** — type-safe check replaces `method_exists()` duck typing
- **Architecture tests allow `CleanArchitecture\Support` in Domain** — `->ignoring('CleanArchitecture\Support')` permits the `HasDomainEvents` interface dependency
- **All console commands return `int`** — `handle()` methods return `self::SUCCESS` instead of `void`, following Laravel command conventions
- **`toKebab()` extracted in `BaseGenerator`** — shared kebab-case logic, `toKebabPlural()` delegates to it
- **Scaffold `wireRoutes()` uses correct method** — `Route::apiResource()` for `api.php`, `Route::resource()` for `web.php`

### Fixed

- **Controller pagination was non-functional** — `index()` created `ListQuery()` with no arguments, always defaulting to page 1; now passes request parameters
- **Unused entity import in delete command** — `clean:command --entity --crud=delete` no longer imports the Entity class (only write repository is needed)
- **`DispatchesDomainEvents` duck typing** — replaced fragile `method_exists()` with `HasDomainEvents` interface check
- **Sanitizer silent data loss** — generated sanitizers returned empty array by default; now spread `...$data` to pass through all fields
- **`wireRoutes()` used `Route::apiResource` for web routes** — now correctly uses `Route::resource()` for `web.php`

## [1.2.2] - 2026-03-21

### Added

- **Laravel 13 compatibility** — `illuminate/support ^13.0` and `orchestra/testbench ^11.0` now supported
- **PHP 8.5 CI coverage** — added PHP 8.5 to the GitHub Actions test matrix

## [1.2.1] - 2026-03-20

### Changed

- **Default namespace prefix** — changed from `App` to `Src` in configuration, aligning the default with the `contexts_path` convention (`src/`)

> **Upgrade note (added retroactively):** this change was breaking for projects that had not published the config file — generated code moved from the `App\` namespace to `Src\`. If you upgrade from ≤ 1.2.0 and relied on the old default, publish the config (`php artisan vendor:publish --tag=clean-architecture-config`) and set `namespace_prefix` back to `App`.

## [1.2.0] - 2026-03-16

### Added

- **Full CRUD scaffold** — `clean:scaffold` now generates all 5 CRUD operations out of the box:
  - `CreateEntity` command + handler with `array $data` constructor and `Entity::create()` + `repository->save()` handler body
  - `UpdateEntity` command + handler with `string $id` + `array $data` constructor
  - `DeleteEntity` command + handler with `string $id` constructor and `repository->delete()` handler body
  - `GetEntity` query + handler with nullable `?ReadModel` return and wired `findById()` call
  - `ListEntities` collection query + handler with pagination passthrough (`$query->page`, `$query->perPage`)
- **Eloquent Model generator** — `clean:model {context} {name}` generates a `HasUuids` Eloquent model in `Infrastructure/Models/` with auto-computed table name (`OrderItem` → `order_items`)
- **Domain Event Dispatcher** — `DispatchesDomainEvents` trait for write repositories; dispatches domain events via Laravel's `event()` helper after entity persistence, with `method_exists()` guard and automatic event clearing to prevent double dispatch
- `--crud` option on `clean:command` — generates CRUD-specific constructors and handler bodies (`create`, `update`, `delete`); scaffold passes this flag automatically
- `--collection` option on `clean:query` — generates a list/collection query with `$page` and `$perPage` pagination parameters instead of `$id`, and a handler that returns `array`
- `toPluralStudly()` helper in `BaseGenerator` — computes plural form for entity names (`Invoice` → `Invoices`, `Category` → `Categories`)
- New stubs: `model.stub`, `list-query.stub`, `list-query-handler.stub`
- New command stub placeholders: `{{CommandConstructor}}`, `{{HandlerBody}}`
- New controller stub placeholders: `{{IndexBody}}`, `{{UpdateBody}}`, `{{DestroyBody}}`

### Changed

- **Controller wiring** — `clean:controller --entity` now injects all 5 handlers (create, update, delete, get, list) and wires all 5 methods (`index`, `show`, `store`, `update`, `destroy`) with working implementations; `show()` includes `abort_if(! $readModel, 404)` null handling; `store()` and `update()` pass sanitized data as `array` (not spread)
- **Repository stubs** — `write-eloquent-repository.stub` and `read-eloquent-repository.stub` now use real `{{Class}}Model` code with explicit `::query()->` builder calls; write repository includes `DispatchesDomainEvents` trait and dispatches events after save; read repository uses `forPage()` for pagination
- **Read repository interface** — `findAll()` now accepts `int $page = 1, int $perPage = 15` pagination parameters
- **Query handlers** — both `query-handler.stub` and `list-query-handler.stub` now use `{{HandlerBody}}` placeholder; wired handlers include actual return statements (`findById()`, `findAll()` with pagination passthrough)
- **Mapper stub** — `toEntity()` now type-hints `{{Class}}Model` instead of `object`
- **Scaffold command** — uses indexed array format internally; generates model, update/delete commands with `--crud` flag, and list query in addition to existing files
- `controller.stub` — all 5 methods now use replaceable placeholders instead of hardcoded TODOs

### Fixed

- Generated repositories are now functional out of the box — no more commented-out Eloquent code requiring manual uncommenting
- Generated controllers wire all 5 RESTful operations instead of leaving `index()`, `update()`, and `destroy()` as TODOs
- Controller `show()` handles null read models with `abort_if` instead of passing null to Resource
- Controller `store()`/`update()` pass sanitized data as `array` parameter, matching the command constructor signatures (`array $data`)
- List query handler passes pagination params (`$query->page, $query->perPage`) to `findAll()` instead of calling without arguments
- All Eloquent calls use explicit `::query()->` builder pattern for PHPStan compatibility and IDE autocompletion

## [1.1.0] - 2026-03-16

### Added

- **Wired scaffold output** — `clean:scaffold` now produces fully connected files out of the box instead of TODO placeholders:
  - Controller is generated with `--entity`, injecting `CreateHandler` and `GetHandler` with working `store()` and `show()` methods
  - ServiceProvider bindings are wired automatically between `// {bindings}` / `// {/bindings}` markers (duplicates are skipped on re-run)
  - Routes are wired with `Route::apiResource('{plural-kebab}', Controller::class)` between `// {routes}` / `// {/routes}` markers
- `--entity` option on `clean:controller` — wires CQRS handler imports, constructor injection, and `store()`/`show()` method bodies; without it, TODO placeholders are generated
- `--routes` option on `clean:context` — controls which route files are generated: `api` (default), `web`, or `both`
- `toKebabPlural()` helper in `BaseGenerator` — converts PascalCase names to plural kebab-case for route resource names (e.g. `LineItem` → `line-items`)
- Binding markers (`// {bindings}` / `// {/bindings}`) in `service-provider.stub` — scaffold inserts real bindings here
- Route markers (`// {routes}` / `// {/routes}`) in `routes.stub` — scaffold inserts apiResource routes here
- New controller stub placeholders: `{{ControllerImports}}`, `{{ControllerConstructor}}`, `{{ShowBody}}`, `{{StoreBody}}`

### Changed

- `service-provider.stub` — `loadRoutes()` now uses a foreach over `['api', 'web']`, loading both route files if they exist (previously hardcoded to `api.php` only)
- `write-eloquent-repository.stub` — `Mapper::toArray($entity)` call is now uncommented in `save()` (only the Eloquent model line remains as TODO)
- `clean:scaffold` now passes `--entity` to `clean:controller` for automatic handler wiring
- `clean:context` `generateRoutes()` respects the new `--routes` option

### Fixed

- Scaffold no longer generates disconnected files — controllers, service providers, and routes are wired together automatically when a bounded context exists
- Graceful handling when scaffolding without a prior `clean:context` — warns about missing ServiceProvider/routes instead of failing

## [1.0.0] - 2026-03-15

### Added

- `clean:context` command to scaffold a full bounded context with DDD folder structure
- `clean:entity` command to generate final domain entities with `create()` factory method and domain event recording (`recordEvent()`/`releaseEvents()`)
- `clean:value-object` command to generate readonly value objects with self-validation
- `clean:repository` command to generate CQRS repository split — `WriteRepository` interface (Domain), `ReadRepository` interface (Application/Contracts), `WriteEloquentRepository`, `ReadEloquentRepository`, and `Mapper` (Infrastructure)
- `clean:specification` command to generate composable domain specifications with `and()`/`or()`/`not()`
- `clean:command` command to generate CQRS command and handler pair with optional `--entity` flag for `WriteRepository` injection
- `clean:query` command to generate CQRS query, handler, and read model with optional `--entity` flag for `ReadRepository` injection
- `clean:read-model` command to generate standalone application read models
- `clean:mapper` command to generate Entity↔Model mappers in Infrastructure layer
- `clean:sanitizer` command to generate input sanitizers in `Application/Sanitizers/`
- `clean:domain-event` command to generate readonly domain events with timestamp in `Domain/Events/`
- `clean:exception` command to generate domain exceptions extending `\DomainException` in `Domain/Exceptions/`
- `clean:controller` command to generate controllers with CQRS dispatch pattern in Presentation layer
- `clean:request` command to generate form requests with authorization in Presentation layer
- `clean:resource` command to generate API resources with field mapping in Presentation layer
- `clean:test` command to generate Pest unit tests for domain entities (configurable via `unit_tests_path`)
- `clean:arch-test` command to generate Pest architecture tests enforcing 7 DDD dependency rules
- `clean:scaffold` command to scaffold a full entity across all layers in one command (17+ files)
- Auto-discovery of context ServiceProviders via `ModuleLoader` with error handling (failed providers are reported, not fatal)
- Auto-registration of PSR-4 autoloading for bounded contexts
- Input validation on all commands — context and name must be PascalCase (e.g. `Billing`, `Invoice`)
- Improved error messages when stub files are missing (shows searched paths and suggests publishing stubs)
- Publishable configuration (`clean-architecture-config`)
- Publishable stubs (`clean-architecture-stubs`) for customization
- Route file generation (`Presentation/Routes/api.php`) with kebab-case prefix derived from context name
- Automatic route loading in context ServiceProvider with `api` middleware
- 24 customizable stubs with `{{Namespace}}`, `{{Class}}`, `{{Context}}`, `{{EntityImport}}`, `{{EntityConstructor}}`, and `{{prefix}}` placeholders

### Configuration

| Option | Default | Description |
|--------|---------|-------------|
| `contexts_path` | `src` | Directory where bounded contexts live |
| `namespace_prefix` | `Src` | Root namespace for contexts |
| `auto_discover` | `true` | Auto-register context ServiceProviders |
| `auto_load` | `true` | Auto-register PSR-4 autoloading |
| `arch_tests_path` | `tests/Feature/Architecture` | Where architecture tests are generated |
| `unit_tests_path` | `tests/Unit/Domain` | Where domain unit tests are generated |

[Unreleased]: https://github.com/ElberCanoles/laravel-clean-architecture/compare/v1.5.0...HEAD
[1.5.0]: https://github.com/ElberCanoles/laravel-clean-architecture/compare/v1.4.0...v1.5.0
[1.4.0]: https://github.com/ElberCanoles/laravel-clean-architecture/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/ElberCanoles/laravel-clean-architecture/compare/v1.2.2...v1.3.0
[1.2.2]: https://github.com/ElberCanoles/laravel-clean-architecture/compare/v1.2.1...v1.2.2
[1.2.1]: https://github.com/ElberCanoles/laravel-clean-architecture/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/ElberCanoles/laravel-clean-architecture/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/ElberCanoles/laravel-clean-architecture/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/ElberCanoles/laravel-clean-architecture/releases/tag/v1.0.0
