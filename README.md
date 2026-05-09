# PCF Addendum Framework

[![PHP 8.5](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![CI](https://github.com/purringCatFoundation/AddendumFramework/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/purringCatFoundation/AddendumFramework/actions/workflows/ci.yml)
[![Docker](https://github.com/purringCatFoundation/AddendumFramework/actions/workflows/docker.yml/badge.svg?branch=main)](https://github.com/purringCatFoundation/AddendumFramework/actions/workflows/docker.yml)
[![CodeQL](https://github.com/purringCatFoundation/AddendumFramework/actions/workflows/codeql.yml/badge.svg?branch=main)](https://github.com/purringCatFoundation/AddendumFramework/actions/workflows/codeql.yml)
[![codecov](https://codecov.io/gh/purringCatFoundation/AddendumFramework/branch/main/graph/badge.svg)](https://codecov.io/gh/purringCatFoundation/AddendumFramework)
[![Security](https://github.com/purringCatFoundation/AddendumFramework/actions/workflows/security.yml/badge.svg?branch=main)](https://github.com/purringCatFoundation/AddendumFramework/actions/workflows/security.yml)

PCF Addendum is a PHP 8.5 API framework for PSR-based HTTP applications. It provides attribute-driven routing, request validation, authentication helpers, middleware composition, CLI commands and compiled HTTP route metadata for production runtimes.

## Contents

- [Requirements](#requirements)
- [Framework Overview](#framework-overview)
- [Design Principles](#design-principles)
- [First Steps](#first-steps)
- [Development Server](#development-server)
- [Documentation](#documentation)

## Requirements

- PHP 8.5
- Composer
- PostgreSQL with `ext-pdo_pgsql`
- Redis for built-in rate limiting, request replay protection and optional Redis HTTP response caching
- `ext-ds`, installed with PHP Installer for Extensions: `pie install php-ds/ext-ds`

## Framework Overview

Addendum applications are built from small action classes. Routes, validation rules, middleware, rate limits and cache policies are declared with PHP attributes on endpoint classes. Runtime handling uses PSR-7 requests/responses and PSR-15 middleware.

Core capabilities:

- Attribute-based routing with `#[Route]`.
- Declarative request validation with `#[ValidateRequest]`.
- JWT authentication helpers and request signature verification.
- PSR-15 middleware pipeline.
- Resource-aware HTTP cache headers and backend integrations.
- Symfony Console based CLI command discovery.
- Compiled HTTP route cache for production runtimes.

## Design Principles

- PHP 8.5 only.
- PostgreSQL only for persistence.
- PSR-first contracts where standards exist.
- Object-oriented application data flow; arrays are reserved for PHP/vendor boundaries.
- Reflection and file scanning belong in CLI/build paths, not in the HTTP hot path.
- Runtime services should be explicit dependencies, not nullable constructor fallbacks.

## First Steps

Install dependencies:

```bash
composer install
```

Create an application class:

```php
<?php
declare(strict_types=1);

namespace App;

use PCF\Addendum\Application\Application;
use PCF\Addendum\Attribute\Actions;
use PCF\Addendum\Attribute\Commands;
use PCF\Addendum\Attribute\Name;
use PCF\Addendum\Attribute\Version;

#[Name('My API')]
#[Version('1.0.0')]
#[Actions(__DIR__ . '/Action')]
#[Commands(__DIR__ . '/Command')]
final class App extends Application
{
}
```

Create an action:

```php
<?php
declare(strict_types=1);

namespace App\Action;

use PCF\Addendum\Action\ActionInterface;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Attribute\ValidateRequest;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Validation\Rules\Email;
use PCF\Addendum\Validation\Rules\Required;

#[Route(path: '/users', method: 'POST')]
#[ValidateRequest('email', new Required(), new Email())]
#[ValidateRequest('password', new Required())]
final class PostUserAction implements ActionInterface
{
    public function __invoke(Request $request): array
    {
        return ['ok' => true];
    }
}
```

Create an HTTP entry point:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

App\App::http();
```

Use the bundled CLI during development:

```bash
./bin/addendum
```

Build compiled HTTP cache when running with compiled routes:

```bash
./bin/addendum cache:warmup
```

## Development Server

The repository includes a Docker Compose development stack with FrankenPHP, PostgreSQL and Redis.

Build and start the server:

```bash
docker compose build frankenphp
docker compose up
```

Open `http://localhost:8080`.

Health check:

```bash
curl -fsS http://localhost:8080/health.php
```

Run PHPUnit through the test profile:

```bash
docker compose --profile test run --rm app
```

Run database pgTAP tests through the database profile:

```bash
docker compose --profile database run --rm database-tests
```

## Documentation

- [HTTP Cache](docs/http-cache.md)
- [Compiled Application Cache](docs/compiled-cache.md)
- [Future Service Container Design](docs/container-design.md)
