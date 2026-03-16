# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com), and this project adheres to [Semantic Versioning](https://semver.org).

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
