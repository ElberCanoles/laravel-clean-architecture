# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com), and this project adheres to [Semantic Versioning](https://semver.org).

## [2.0.0] - 2026-03-15

### Added

- **CQRS Repository Split**: `clean:repository` now generates 5 files — `WriteRepository` interface (Domain), `ReadRepository` interface (Application/Contracts), `WriteEloquentRepository`, `ReadEloquentRepository`, and `Mapper` (Infrastructure)
- `clean:mapper` command to generate Entity↔Model mappers individually
- `clean:sanitizer` command to generate input sanitizers in `Application/Sanitizers/`
- `clean:domain-event` command to generate readonly domain events with timestamp in `Domain/Events/`
- `clean:exception` command to generate domain exceptions extending `\DomainException` in `Domain/Exceptions/`
- `clean:test` command to generate Pest unit tests for domain entities in configurable `unit_tests_path`
- `clean:scaffold` command to scaffold a full entity across all layers in one command (17+ files)
- `--entity` flag on `clean:command` and `clean:query` to auto-inject typed repository dependencies into handlers
- `unit_tests_path` configuration option (default: `tests/Unit/Domain`)
- New folders created by `clean:context`: `Application/Contracts`, `Application/Sanitizers`, `Domain/Events`, `Domain/Exceptions`
- New stubs: `write-repository`, `read-repository`, `write-eloquent-repository`, `read-eloquent-repository`, `mapper`, `sanitizer`, `domain-event`, `domain-exception`, `unit-test`
- Architecture tests now enforce 7 rules per context (added Application→Presentation and Application→Infrastructure dependency checks)

### Changed

- Entity stub now includes `create()` factory method, `recordEvent()`, and `releaseEvents()` for domain event support
- Value Object stub now self-validates in constructor (throws `InvalidArgumentException` on empty value)
- Specification stub now includes `and()`, `or()`, `not()` composition methods
- Command and Query stubs are now `readonly class`
- Command handler stub supports conditional `WriteRepository` injection via `{{EntityImport}}`/`{{EntityConstructor}}` placeholders
- Query handler stub supports conditional `ReadRepository` injection via same placeholders
- Controller stub now imports Request/Resource, includes constructor and full CQRS dispatch pattern (index, show, store, update, destroy)
- Service Provider stub now includes repository binding examples and auto-discovery comment
- Resource stub now maps fields explicitly instead of delegating to `parent::toArray()`
- Request stub now includes `authorize()` with TODO and rule examples
- Architecture test stub adds Application layer dependency rules
- Query read model stub differentiates from standalone with query-specific comment
- README fully rewritten to reflect CQRS/DDD patterns, all new commands, and updated stubs table

### Removed

- `repository.stub` (replaced by `write-repository.stub` and `read-repository.stub`)
- `eloquent-repository.stub` (replaced by `write-eloquent-repository.stub` and `read-eloquent-repository.stub`)

## [1.0.2] - 2026-03-15

### Fixed

- Removed invalid `use function Pest\Arch\expect` import from `arch-test.stub` (`expect()` is already a global Pest function)
- Changed default architecture tests path from `tests/Architecture` to `tests/Feature/Architecture` so generated tests are included in PHPUnit's `Feature` suite

## [1.0.1] - 2026-03-15

### Added

- `clean:controller` command to generate controllers in the Presentation layer with CRUD methods
- `clean:request` command to generate form requests in the Presentation layer
- `clean:resource` command to generate API resources (JsonResource) in the Presentation layer
- Route file generation (`Presentation/Routes/api.php`) when creating a bounded context
- Automatic route loading in the context ServiceProvider with `api` middleware
- Kebab-case route prefix derived from context name (e.g. `OrderManagement` -> `order-management`)
- Presentation subfolders (`Controllers`, `Requests`, `Resources`, `Routes`) created by `clean:context`
- New stubs: `controller.stub`, `request.stub`, `resource.stub`, `routes.stub`

### Changed

- `service-provider.stub` now includes `loadRoutes()` method for automatic route registration
- `clean:context` generates `Presentation/` with organized subfolders instead of an empty directory

## [1.0.0] - 2026-03-15

### Added

- `clean:context` command to scaffold a full bounded context with DDD folder structure
- `clean:entity` command to generate final domain entities
- `clean:value-object` command to generate readonly value objects
- `clean:repository` command to generate repository interface and Eloquent implementation
- `clean:specification` command to generate domain specifications
- `clean:command` command to generate CQRS command and handler pair
- `clean:query` command to generate CQRS query, handler, and read model
- `clean:read-model` command to generate standalone read models
- `clean:arch-test` command to generate Pest architecture tests enforcing DDD rules
- Auto-discovery of context ServiceProviders via `ModuleLoader`
- Auto-registration of PSR-4 autoloading for bounded contexts
- Publishable configuration (`clean-architecture-config`)
- Publishable stubs (`clean-architecture-stubs`) for customization
