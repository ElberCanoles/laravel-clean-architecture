# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com), and this project adheres to [Semantic Versioning](https://semver.org).

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
