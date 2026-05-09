# Compiled Application Cache

Addendum can compile HTTP route metadata into PHP files so production requests do not scan action directories or read PHP attributes in the router hot path.

## Generated Files

Compiled files are written under `APP_CACHE_DIR`.

| File | Purpose |
|------|---------|
| `routes.php` | Returns a factory for a `RouteCollection` with route patterns, original paths, middleware metadata and resource policies. |
| `metadata.php` | Contains build metadata such as route count and build time. |
| `app.php` | Returns a factory for the compiled HTTP `App` bootstrap. |

`var/cache/` is ignored by git. Rebuild these files in each environment instead of committing them.

## Environment

```env
APP_ENV=dev
APP_CACHE=auto
APP_CACHE_DIR=var/cache/addendum/compiled
```

| Variable | Values | Meaning |
|----------|--------|---------|
| `APP_ENV` | `dev`, `prod` | `dev` refreshes compiled routes on request when cache is enabled. |
| `APP_CACHE` | `off`, `auto`, `required` | `off` builds routes dynamically, `auto` loads or rebuilds cache, `required` fails on invalid cache. |
| `APP_CACHE_DIR` | path | Directory for compiled PHP files. Relative paths are resolved from the project root/current working directory. |

## Commands

Warm compiled cache:

```bash
./bin/addendum cache:warmup
```

Scan explicit action paths:

```bash
./bin/addendum cache:warmup --action-path=src/Action --action-path=vendor/acme/package/src/Action
```

Inspect compiled cache:

```bash
./bin/addendum cache:debug
```

Show route middleware and resource policies:

```bash
./bin/addendum cache:debug --details
```

Filter by route path or real request path:

```bash
./bin/addendum cache:debug --path=/v1/users/123
```

Print JSON:

```bash
./bin/addendum cache:debug --json
```

Return a non-zero status for invalid compiled cache:

```bash
./bin/addendum cache:debug --strict
```

Remove generated compiled cache files:

```bash
./bin/addendum cache:cleanup
```

## When To Rebuild

Run `cache:warmup` after changing:

- route attributes or action classes;
- middleware attributes or middleware metadata providers;
- validation attributes or validators used by `#[ValidateRequest]`;
- `#[ResourcePolicy]` declarations;
- compiled cache generator code.

## Runtime Behavior

The compiled route file contains enough metadata for request handling without route scanning. Dynamic discovery remains available for CLI commands and cache warmup because those paths are allowed to use reflection and file scanning.

`cache:debug` reads compiled files only. It does not use the reflective route listing command.

For a compiled HTTP entry point, use `PCF\Addendum\Application\Main` after running `cache:warmup`:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

(new PCF\Addendum\Application\Main())->execute();
```

The legacy `Application::http()` entry point remains available for attribute-driven applications, but compiled `Main` is the intended production path.
