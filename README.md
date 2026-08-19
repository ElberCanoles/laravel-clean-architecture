# Laravel Clean Architecture

[![CI](https://github.com/ElberCanoles/laravel-clean-architecture/actions/workflows/ci.yml/badge.svg)](https://github.com/ElberCanoles/laravel-clean-architecture/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/elber/laravel-clean-architecture.svg)](https://packagist.org/packages/elber/laravel-clean-architecture)
[![Total Downloads](https://img.shields.io/packagist/dt/elber/laravel-clean-architecture.svg)](https://packagist.org/packages/elber/laravel-clean-architecture)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-8892BF.svg)](https://php.net)
[![Laravel 11–13](https://img.shields.io/badge/Laravel-11--13-FF2D20.svg)](https://laravel.com)

A Laravel package that provides scaffolding for **Domain-Driven Design (DDD)**, **Clean Architecture**, and **CQRS**. It generates bounded contexts with separated read/write repositories, domain events, mappers, sanitizers, and architecture tests — enforcing clean dependency rules from day one.

---

## Table of Contents

- [Why this package](#why-this-package)
- [Architecture Overview](#architecture-overview)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Commands Reference](#commands-reference)
  - [The `--entity` flag](#the---entity-flag)
  - [The `--crud` flag](#the---crud-flag)
  - [The `--collection` flag](#the---collection-flag)
  - [The `--id-type` flag](#the---id-type-flag)
  - [The `--routes` flag](#the---routes-flag)
- [Configuration](#configuration)
- [Auto-discovery and Autoloading](#auto-discovery-and-autoloading)
- [Architecture Tests](#architecture-tests)
- [Customizing Stubs](#customizing-stubs)
- [Requirements](#requirements)
- [Upgrading](#upgrading)
- [Security](#security)
- [License](#license)

---

## Why this package

Module packages give you boundaries; this one also gives you **an opinion about what goes inside them** — and tests that enforce it.

| | [nwidart/laravel-modules](https://github.com/nWidart/laravel-modules) · [internachi/modular](https://github.com/InterNACHI/modular) | **laravel-clean-architecture** |
|---|---|---|
| Module boundaries | ✅ | ✅ Bounded contexts with auto-discovery |
| Internal structure | Unopinionated (MVC by default) | **DDD layers + CQRS**, generated wired end to end |
| Dependency rules | — | **9 generated Pest architecture tests** per context, incl. "Application never imports `Illuminate\*`" |
| Read/write split | — | Write repositories (Domain) + read repositories with `PaginatedResult` (Application) |
| Domain events | — | Recorded in entities, dispatched on persist |
| composer.json edits | Required or plugin-based autoloading | **None** — PSR-4 registered automatically (cacheable via `clean:cache`) |

If you want generic modules with full freedom inside, those packages are excellent. If you want each module to *be* a clean architecture, this is the one.

---

## Architecture Overview

Each bounded context is divided into four layers with strict dependency rules — the inner layers know nothing about the outer ones:

| Layer | Contains | May depend on |
|-------|----------|---------------|
| **Domain** | Entities, value objects, write-repository interfaces, specifications, events, exceptions | Nothing (pure PHP) |
| **Application** | Commands, queries, handlers, read models, read-repository contracts, sanitizers | Domain |
| **Infrastructure** | Eloquent models, repository implementations, mappers, the context ServiceProvider | Domain + Application |
| **Presentation** | Controllers, form requests, API resources, routes | Application |

The generated architecture tests enforce these rules on every CI run — including that the Application layer never imports `Illuminate\*`.

**→ Read the full [Architecture Guide](docs/architecture.md)** for the C4 diagrams, layer-by-layer code walkthroughs, and the Dependency Rule in depth.

---

## Installation

```bash
composer require elber/laravel-clean-architecture
```

The ServiceProvider is auto-discovered by Laravel. No manual registration needed.

The generated architecture tests run on [Pest](https://pestphp.com), so your application needs it as a dev dependency:

```bash
composer require --dev pestphp/pest pestphp/pest-plugin-arch
```

Optional publishing:

```bash
php artisan vendor:publish --tag=clean-architecture-config   # config file
php artisan vendor:publish --tag=clean-architecture-stubs    # customizable stubs
```

---

## Quick Start

### 1. Scaffold a context and an entity

```bash
php artisan clean:context Billing
php artisan clean:scaffold Billing Invoice   # add --id-type=ulid for ULID keys
```

`clean:scaffold` generates **24 fully wired files** across all four layers, plus the migration, and wires the ServiceProvider bindings and the resource route automatically:

```
src/Billing/
├── Domain/
│   ├── Entities/Invoice.php                     # factory methods + domain events
│   ├── Exceptions/InvoiceNotFound.php           # renders as HTTP 404
│   └── Repositories/InvoiceWriteRepository.php  # ofId() / save() / delete()
├── Application/
│   ├── Commands/
│   │   ├── CreateInvoice/  (Command + Handler)  # id travels in the command
│   │   ├── UpdateInvoice/  (Command + Handler)  # load → guard → save
│   │   └── DeleteInvoice/  (Command + Handler)  # guards missing aggregates
│   ├── Queries/
│   │   ├── GetInvoice/     (Query + Handler)    # nullable read model
│   │   └── ListInvoices/   (Query + Handler)    # PaginatedResult
│   ├── Contracts/InvoiceReadRepository.php
│   ├── ReadModels/InvoiceReadModel.php
│   └── Sanitizers/InvoiceSanitizer.php
├── Infrastructure/
│   ├── BillingServiceProvider.php               # bindings auto-wired
│   ├── Models/InvoiceModel.php                  # HasUuids / HasUlids
│   ├── InvoiceWriteEloquentRepository.php       # dispatches domain events
│   ├── InvoiceReadEloquentRepository.php        # deterministic pagination
│   └── InvoiceMapper.php                        # Entity ↔ Model bridge
└── Presentation/
    ├── Controllers/InvoiceController.php        # all 5 CQRS handlers wired
    ├── Requests/InvoiceRequest.php
    ├── Resources/InvoiceResource.php
    └── Routes/api.php                           # apiResource auto-wired

database/migrations/2026_..._create_invoices_table.php
tests/Feature/Architecture/BillingArchTest.php   # 9 dependency rules (from clean:context)
```

Prefer composing piece by piece? Every artifact has its own command (`clean:entity`, `clean:repository`, `clean:command --crud=…`, `clean:controller --entity=…`, …) — see the [Commands Reference](#commands-reference). Names are normalized for you: `clean:entity billing invoice` and `clean:entity Billing Invoice` are equivalent.

### 2. Fill in what the generator cannot know

The scaffold is wired end to end, but your domain fields are yours. Until you complete these five TODOs, `POST` persists only the generated id:

| File | What to complete |
|------|------------------|
| `Presentation/Requests/InvoiceRequest.php` | Validation `rules()` — `validated()` returns `[]` while empty |
| `Infrastructure/Models/InvoiceModel.php` | `$fillable` and casts for your columns |
| `Infrastructure/InvoiceMapper.php` | `toArray()` / `toEntity()` field mapping |
| `Domain/Entities/Invoice.php` | Your entity's properties and behavior |
| `Application/Commands/*/…Handler.php` | Apply `$command->data` where the `TODO` marks it |

The generated migration also starts with just `id` + `timestamps` — add your columns.

### 3. Serve it

```bash
php artisan migrate
php artisan route:list --path=billing
```

```bash
curl -X POST http://localhost/api/billing/invoices \
     -H "Content-Type: application/json" -H "Accept: application/json" -d '{}'
# → 201 {"id":"0198c5f2-…"}  + Location: /api/billing/invoices/0198c5f2-…

curl http://localhost/api/billing/invoices -H "Accept: application/json"
# → 200 {"data":[{"id":"0198c5f2-…"}],"meta":{"total":1,"page":1,"per_page":15}}

curl -X DELETE http://localhost/api/billing/invoices/does-not-exist -H "Accept: application/json"
# → 404 {"message":"Invoice with id [does-not-exist] was not found."}
```

> Route prefixes come from your app's routing setup: context routes register under the kebab-case context prefix (`/billing/...`), inside whatever prefix your `bootstrap/app.php` gives the `api` middleware group.

Then run the generated architecture tests — they now guard your dependency rules on every CI run:

```bash
vendor/bin/pest tests/Feature/Architecture/
```

---

## Commands Reference

All commands support the `--force` flag to overwrite existing files (for the scaffold-generated migration, `--force` overwrites the existing migration file in place). Without `--force`, a command that finds an existing file warns and exits with code 1; invalid input (bad names, PHP reserved words, unknown option values) prints an error and exits with code 2.

Names must be PascalCase (`Billing`, `Invoice`) and must not be PHP reserved words.

| Command | Description | Output |
|---------|-------------|--------|
| `clean:context {name} [--routes=]` | Create bounded context with folders, ServiceProvider, routes, arch tests | Full folder structure |
| `clean:scaffold {context} {name} [--id-type=]` | Scaffold full CRUD entity across all layers (wires controller, SP bindings, routes) | 24 files |
| `clean:entity {context} {name}` | Domain entity with factory method and event recording | `Domain/Entities/{Name}.php` |
| `clean:model {context} {name} [--id-type=]` | Eloquent model with `HasUuids`/`HasUlids` and auto-computed table name | `Infrastructure/Models/{Name}Model.php` |
| `clean:repository {context} {name}` | CQRS repositories (Write + Read interfaces, Eloquent impls, mapper) | 5 files |
| `clean:read-model {context} {name}` | Standalone readonly read model | `Application/ReadModels/{Name}ReadModel.php` |
| `clean:value-object {context} {name}` | Readonly value object with validation | `Domain/ValueObjects/{Name}.php` |
| `clean:specification {context} {name}` | Composable specification with `and()`/`or()`/`not()` | `Domain/Specifications/{Name}Specification.php` |
| `clean:domain-event {context} {name}` | Readonly domain event with timestamp | `Domain/Events/{Name}Event.php` |
| `clean:exception {context} {name}` | Domain exception extending `\DomainException` | `Domain/Exceptions/{Name}Exception.php` |
| `clean:command {context} {name} [--entity=] [--crud=] [--id-type=]` | CQRS command + handler (optionally injects WriteRepository with CRUD-specific logic) | `Application/Commands/{Name}/` |
| `clean:query {context} {name} [--entity=] [--collection]` | CQRS query + handler (optionally injects ReadRepository) | `Application/Queries/{Name}/` |
| `clean:mapper {context} {name}` | Entity-Model mapper | `Infrastructure/{Name}Mapper.php` |
| `clean:sanitizer {context} {name}` | Input sanitizer | `Application/Sanitizers/{Name}Sanitizer.php` |
| `clean:controller {context} {name} [--entity=]` | Controller with full CRUD dispatch pattern (optionally wires all 5 handlers) | `Presentation/Controllers/{Name}Controller.php` |
| `clean:request {context} {name}` | Form request with authorization | `Presentation/Requests/{Name}Request.php` |
| `clean:resource {context} {name}` | API resource with field mapping | `Presentation/Resources/{Name}Resource.php` |
| `clean:test {context} {name}` | Pest unit test for domain entity | `tests/Unit/Domain/{Context}/{Name}Test.php` |
| `clean:arch-test {context}` | Architecture dependency tests | `tests/Feature/Architecture/{Context}ArchTest.php` |

### The `--entity` flag

The `clean:command`, `clean:query`, and `clean:controller` commands accept an optional `--entity` flag that automatically wires the generated code:

```bash
# Handler will inject InvoiceWriteRepository
php artisan clean:command Billing PayInvoice --entity=Invoice

# Handler will inject InvoiceReadRepository
php artisan clean:query Billing GetInvoice --entity=Invoice

# Controller will inject all 5 handlers (create, update, delete, get, list)
php artisan clean:controller Billing Invoice --entity=Invoice
```

Without `--entity`, the generated files get TODO placeholders instead. The `clean:scaffold` command passes `--entity` automatically.

### The `--crud` flag

The `clean:command` command accepts a `--crud` flag that generates CRUD-specific command constructors and handler bodies:

```bash
# Constructor: string $id + array $data — Handler: Entity::create($command->id) + repository->save()
php artisan clean:command Billing CreateInvoice --entity=Invoice --crud=create

# Constructor: string $id + array $data — Handler: ofId() → throw NotFound → save()
php artisan clean:command Billing UpdateInvoice --entity=Invoice --crud=update

# Constructor: string $id — Handler: ofId() guard → repository->delete($command->id)
php artisan clean:command Billing DeleteInvoice --entity=Invoice --crud=delete
```

| `--crud` value | Constructor | Handler body |
|---------------|-------------|-------------|
| `create` | `public string $id, public array $data` | Creates entity via factory method + saves |
| `update` | `public string $id, public array $data` | Loads via `ofId()`, throws `{Entity}NotFound`, saves |
| `delete` | `public string $id` | Guards via `ofId()`, throws `{Entity}NotFound`, deletes |
| _(none)_ | `public string $id` | TODO placeholder |

> `update` and `delete` also generate a `Domain/Exceptions/{Entity}NotFound` exception (if missing), which the package renders as an HTTP 404 JSON response out of the box.

The `clean:scaffold` command passes `--crud` automatically to each command (`create`, `update`, `delete`).

### The `--collection` flag

The `clean:query` command accepts a `--collection` flag to generate a list/collection query with pagination parameters instead of a single-entity query:

```bash
# Generates query with $page/$perPage params and handler returning PaginatedResult
php artisan clean:query Billing ListInvoices --entity=Invoice --collection
```

The `clean:scaffold` command uses this flag automatically for the `ListEntities` query.

### The `--id-type` flag

The package supports two identifier strategies for generated models, migrations, and controllers: `uuid` (default) and `ulid`. Accepted by `clean:scaffold`, `clean:model`, `clean:controller`, and `clean:command`.

```bash
# UUIDv7 keys — the default
php artisan clean:scaffold Billing Invoice

# ULID keys
php artisan clean:scaffold Billing Invoice --id-type=ulid
```

| `--id-type` | Eloquent model | Migration | Controller `store()` |
|-------------|----------------|-----------|----------------------|
| `uuid` _(default)_ | `use HasUuids;` | `$table->uuid('id')->primary()` | `$id = (string) Str::uuid7();` |
| `ulid` | `use HasUlids;` | `$table->ulid('id')->primary()` | `$id = (string) Str::ulid();` |

> The id is generated in the controller and travels in the create command, keeping the Application layer framework-free (and letting the arch tests enforce it).

Both are time-ordered, so either choice keeps primary keys index-friendly. ULIDs are shorter (26 chars vs 36) and are lexicographically sortable as strings.

To switch the default for the whole project, publish the config and set `id_type`:

```php
// config/clean-architecture.php
'id_type' => 'ulid',
```

The flag takes precedence over the config value, and `clean:scaffold` resolves it once and forwards it to `clean:model` and `clean:command`, so every generated file for an entity shares the same strategy. Accepted by `clean:scaffold`, `clean:model`, and `clean:command`; any other value fails fast with an `InvalidArgumentException`.

Domain entities are unaffected — they take a plain `string $id` regardless of strategy, keeping the domain layer free of persistence concerns.

### The `--routes` flag

The `clean:context` command accepts an optional `--routes` flag to control which route files are generated:

```bash
php artisan clean:context Billing                  # generates api.php (default)
php artisan clean:context Billing --routes=web     # generates web.php
php artisan clean:context Billing --routes=both    # generates api.php + web.php
```

The ServiceProvider loads both `api.php` and `web.php` automatically if they exist, applying the corresponding middleware.

---

## Configuration

| Option | Default | Description |
|--------|---------|-------------|
| `contexts_path` | `src` | Directory where bounded contexts live, relative to `base_path()` |
| `namespace_prefix` | `Src` | Root namespace for contexts (`Src\Billing`, `Src\Inventory`, etc.) |
| `id_type` | `uuid` | Identifier strategy for models, migrations and create handlers (`uuid` or `ulid`) |
| `auto_discover` | `true` | Auto-register `{Context}ServiceProvider` from each context |
| `auto_load` | `true` | Auto-register PSR-4 autoloading for all `src/` contexts |
| `render_domain_exceptions` | `true` | Render uncaught `\DomainException`s as JSON (`422`, or the status from `ProvidesHttpStatus`) on requests expecting JSON |
| `arch_tests_path` | `tests/Feature/Architecture` | Where generated architecture tests are stored |
| `unit_tests_path` | `tests/Unit/Domain` | Where generated domain unit tests are stored |

---

## Auto-discovery and Autoloading

### ServiceProvider Auto-discovery

When `auto_discover` is enabled, the package scans each bounded context for a ServiceProvider at:

```
src/{Context}/Infrastructure/{Context}ServiceProvider.php
```

These providers are **automatically registered** with Laravel's service container. The `clean:context` command generates this ServiceProvider for you.

### PSR-4 Autoloading

When `auto_load` is enabled, the package registers PSR-4 autoloading for every directory in your contexts path:

```
Src\Billing\    --> src/Billing/
Src\Inventory\  --> src/Inventory/
Src\Shipping\   --> src/Shipping/
```

**No manual `composer.json` changes needed** when you add new bounded contexts.

---

## Architecture Tests

The `clean:context` and `clean:arch-test` commands generate Pest architecture tests that **enforce DDD dependency rules** automatically.

Generated tests for each context (9 rules):

| Test | What it enforces |
|------|-----------------|
| Domain does not depend on Infrastructure | Domain layer has zero external dependencies (allows `CleanArchitecture\Support`) |
| Domain does not depend on Application | Domain never calls use cases or handlers |
| Application does not depend on Presentation | Use cases never reference controllers or requests |
| Application does not depend on Infrastructure | Use cases never reference Eloquent or providers |
| Application does not depend on the framework | Use cases never import `Illuminate\*` — the Dependency Rule, enforced |
| Code declares strict types | Every file in the context uses `declare(strict_types=1)` |
| Entities are final classes | Prevents inheritance that could break invariants |
| Repositories in Domain are interfaces | Domain defines contracts, never implementations |
| Value Objects are readonly | Guarantees immutability |

> **Note:** The Domain layer is allowed to depend on `CleanArchitecture\Support` (e.g. `HasDomainEvents` interface). This is explicitly allowed in the architecture tests via `->ignoring('CleanArchitecture\Support')`.

Run them with:

```bash
vendor/bin/pest tests/Feature/Architecture/
```

These tests integrate into your CI pipeline and **fail the build** if anyone introduces a dependency rule violation.

---

## Customizing Stubs

Publish the stubs to your project:

```bash
php artisan vendor:publish --tag=clean-architecture-stubs
```

This copies all stubs to `stubs/clean-architecture/`. Edit them to match your team's conventions. The generators will use your custom stubs instead of the defaults.

> **Upgrading to 1.4:** stubs published before ULID support hardcode the identifier and lack the `{{IdTrait}}` / `{{idType}}` placeholders, so `--id-type` has no effect on them. The generators warn when this happens; re-publish with `--force` (or add the placeholders by hand) to pick up the new behaviour.

Available stubs:

| Stub | Used by | Placeholders |
|------|---------|-------------|
| `entity.stub` | `clean:entity` | `{{Namespace}}`, `{{Class}}` |
| `model.stub` | `clean:model` | `{{Namespace}}`, `{{Class}}`, `{{table}}`, `{{IdTrait}}` |
| `write-repository.stub` | `clean:repository` | `{{Namespace}}`, `{{Class}}` |
| `read-repository.stub` | `clean:repository` | `{{Namespace}}`, `{{Class}}` |
| `write-eloquent-repository.stub` | `clean:repository` | `{{Namespace}}`, `{{Class}}` |
| `read-eloquent-repository.stub` | `clean:repository` | `{{Namespace}}`, `{{Class}}` |
| `mapper.stub` | `clean:repository`, `clean:mapper` | `{{Namespace}}`, `{{Class}}` |
| `value-object.stub` | `clean:value-object` | `{{Namespace}}`, `{{Class}}` |
| `specification.stub` | `clean:specification` | `{{Namespace}}`, `{{Class}}` |
| `read-model.stub` | `clean:read-model` | `{{Namespace}}`, `{{Class}}` |
| `command.stub` | `clean:command` | `{{Namespace}}`, `{{Class}}`, `{{CommandConstructor}}` |
| `command-handler.stub` | `clean:command` | `{{Namespace}}`, `{{Class}}`, `{{EntityImport}}`, `{{EntityConstructor}}`, `{{HandlerBody}}` |
| `query.stub` | `clean:query` | `{{Namespace}}`, `{{Class}}` |
| `query-handler.stub` | `clean:query` | `{{Namespace}}`, `{{Class}}`, `{{EntityImport}}`, `{{EntityConstructor}}`, `{{ReturnType}}`, `{{HandlerBody}}` |
| `list-query.stub` | `clean:query --collection` | `{{Namespace}}`, `{{Class}}` |
| `list-query-handler.stub` | `clean:query --collection` | `{{Namespace}}`, `{{Class}}`, `{{EntityImport}}`, `{{EntityConstructor}}`, `{{ReturnType}}`, `{{HandlerBody}}` |
| `domain-event.stub` | `clean:domain-event` | `{{Namespace}}`, `{{Class}}` |
| `domain-exception.stub` | `clean:exception` | `{{Namespace}}`, `{{Class}}` |
| `not-found-exception.stub` | `clean:command --crud=update\|delete`, `clean:scaffold` | `{{Namespace}}`, `{{Class}}` |
| `sanitizer.stub` | `clean:sanitizer` | `{{Namespace}}`, `{{Class}}` |
| `unit-test.stub` | `clean:test` | `{{Namespace}}`, `{{Class}}` |
| `service-provider.stub` | `clean:context` | `{{Namespace}}`, `{{Context}}`, `// {bindings}` / `// {/bindings}` markers |
| `routes.stub` | `clean:context` | `{{prefix}}`, `// {routes}` / `// {/routes}` markers |
| `controller.stub` | `clean:controller` | `{{Namespace}}`, `{{Class}}`, `{{ControllerImports}}`, `{{ControllerConstructor}}`, `{{IndexBody}}`, `{{ShowBody}}`, `{{StoreBody}}`, `{{UpdateBody}}`, `{{DestroyBody}}` |
| `request.stub` | `clean:request` | `{{Namespace}}`, `{{Class}}` |
| `resource.stub` | `clean:resource` | `{{Namespace}}`, `{{Class}}` |
| `migration.stub` | `clean:scaffold` | `{{table}}`, `{{idType}}` |
| `arch-test.stub` | `clean:arch-test` | `{{Namespace}}`, `{{Context}}` |

---

## Requirements

- PHP 8.2+
- Laravel 11.17+, 12.0+, or 13.0+

> **Laravel 11 support is deprecated** and will be removed in v2.0 — Laravel 11 reached end of life in March 2026. Laravel 12 or 13 is recommended. The 11.17 floor exists because generated create handlers use `Str::uuid7()`, introduced in that release.

### Dev dependencies (for architecture tests)

- [Pest](https://pestphp.com/) ^3.0
- [Pest Architecture Plugin](https://pestphp.com/docs/arch-testing) ^3.0

---

## Upgrading

Breaking changes between versions are documented in [UPGRADING.md](UPGRADING.md). The full history lives in the [CHANGELOG](CHANGELOG.md).

---

## Security

Please report vulnerabilities privately via [GitHub Security Advisories](https://github.com/ElberCanoles/laravel-clean-architecture/security/advisories/new) — see [SECURITY.md](SECURITY.md). Do not open public issues for security problems.

---

## License

This package is open-sourced software licensed under the [MIT License](LICENSE).
