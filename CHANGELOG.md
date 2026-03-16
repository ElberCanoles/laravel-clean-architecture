# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com), and this project adheres to [Semantic Versioning](https://semver.org).

## [1.2.0] - 2026-03-16

### Added

- **Full CRUD scaffold** — `clean:scaffold` now generates all 5 CRUD operations out of the box:
  - `CreateEntity` command + handler (already existed)
  - `UpdateEntity` command + handler (new)
  - `DeleteEntity` command + handler (new)
  - `GetEntity` query + handler (already existed)
  - `ListEntities` collection query + handler with pagination (new)
- **Eloquent Model generator** — `clean:model {context} {name}` generates a `HasUuids` Eloquent model in `Infrastructure/Models/` with auto-computed table name (`OrderItem` → `order_items`)
- **Domain Event Dispatcher** — `DispatchesDomainEvents` trait for write repositories; dispatches domain events via Laravel's `event()` helper after entity persistence, with `method_exists()` guard and automatic event clearing to prevent double dispatch
- `--collection` option on `clean:query` — generates a list/collection query with `$page` and `$perPage` pagination parameters instead of `$id`, and a handler that returns `array`
- `toPluralStudly()` helper in `BaseGenerator` — computes plural form for entity names (`Invoice` → `Invoices`, `Category` → `Categories`)
- New stubs: `model.stub`, `list-query.stub`, `list-query-handler.stub`
- New controller stub placeholders: `{{IndexBody}}`, `{{UpdateBody}}`, `{{DestroyBody}}`

### Changed

- **Controller wiring** — `clean:controller --entity` now injects all 5 handlers (create, update, delete, get, list) and wires all 5 methods (`index`, `show`, `store`, `update`, `destroy`) with working implementations
- **Repository stubs** — `write-eloquent-repository.stub` and `read-eloquent-repository.stub` now use real `{{Class}}Model` code instead of commented-out `YourEloquentModel` placeholders; write repository includes `DispatchesDomainEvents` trait and dispatches events after save
- **Mapper stub** — `toEntity()` now type-hints `{{Class}}Model` instead of `object`
- **Scaffold command** — uses indexed array format internally; generates model, update/delete commands, and list query in addition to existing files
- `controller.stub` — all 5 methods now use replaceable placeholders instead of hardcoded TODOs

### Fixed

- Generated repositories are now functional out of the box — no more commented-out Eloquent code requiring manual uncommenting
- Generated controllers wire all 5 RESTful operations instead of leaving `index()`, `update()`, and `destroy()` as TODOs

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
| `namespace_prefix` | `App` | Root namespace for contexts |
| `auto_discover` | `true` | Auto-register context ServiceProviders |
| `auto_load` | `true` | Auto-register PSR-4 autoloading |
| `arch_tests_path` | `tests/Feature/Architecture` | Where architecture tests are generated |
| `unit_tests_path` | `tests/Unit/Domain` | Where domain unit tests are generated |
