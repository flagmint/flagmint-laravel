# Changelog

All notable changes to the Flagmint Laravel SDK will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

> **Note:** This package is pre-stable. Breaking changes may occur before `v1.0.0`.
> It requires [`flagmint/php-sdk`](https://github.com/flagmint/flagmint-php-sdk) `^0.1`.

---

## [0.1.0] — 2026-10-06

### Added

- **Service provider** with publishable `config/flagmint.php` and env support
  (`FLAGMINT_SDK_KEY` / `FLAGMINT_API_KEY`).
- **Facade** `Flagmint\Laravel\Facades\Flagmint` for `bool`, `isEnabled`,
  typed readers, `track` / `trackError`, `ready`, and `refresh`.
- **Per-request context** via `RequestContext` and middleware
  `flagmint.context` / `SetFlagmintContext` (safe for FPM; pass explicit
  context in jobs/CLI).
- **Blade** `@feature` / `@else` / `@endfeature` directive.
- **Artisan** `flagmint:refresh` to warm shared rules.
- **Cache drivers**: Laravel Cache adapter as default (`FLAGMINT_CACHE_DRIVER=laravel`),
  plus memory / Redis / custom `CacheAdapter` class options.
- **Queue-backed event flush** (`FlushFlagmintEvents`) when
  `flagmint.queue_events` is enabled.
- Orchestra Testbench feature tests for facade, Blade, middleware, Artisan, and
  event jobs.
- BSD-3-Clause license.

[0.1.0]: https://github.com/flagmint/flagmint-laravel/releases/tag/v0.1.0
