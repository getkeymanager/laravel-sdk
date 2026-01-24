# Changelog

All notable changes to the License Manager Laravel SDK will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-15

### Added
- Initial release of Laravel SDK
- Service provider with auto-discovery
- Facade support for easy API access
- Route protection middleware (`license.validate`, `license.feature`)
- Session-based caching for validation results
- Three Artisan commands:
  - `license:validate` - Validate license keys
  - `license:activate` - Activate licenses
  - `license:deactivate` - Deactivate licenses
- Configuration file with environment variable support
- Optional Laravel logging integration
- Support for Laravel 10, 11, and 12
- Complete PHPDoc annotations
- Comprehensive README with examples
- PSR-4 autoloading
- MIT License

### Features
- Wraps all 39 methods from base PHP SDK v2.0.0
- Automatic hardware ID generation
- Multi-source license key detection (header, query, body, session)
- JSON response support for API endpoints
- Configurable redirect routes for middleware
- Feature-flag based route protection
- Request-attached license data for controllers

## [Unreleased]

### Planned
- Blade directives for feature gates (`@license`, `@feature`)
- Queue support for bulk operations
- Cache driver integration (Redis, Memcached)
- Rate limiting middleware
- License verification scheduler
- Events and listeners for license operations
- Custom exception classes
- Advanced test suite with mock responses

---

For upgrade instructions and migration guides, see [UPGRADE.md](UPGRADE.md).
