# PCF Addendum Framework

[![PHP 8.5](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![CI](https://github.com/purringCatFoundation/AddendumFramework/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/purringCatFoundation/AddendumFramework/actions/workflows/ci.yml)
[![Quality](https://github.com/purringCatFoundation/AddendumFramework/actions/workflows/quality.yml/badge.svg?branch=main)](https://github.com/purringCatFoundation/AddendumFramework/actions/workflows/quality.yml)
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
- [JWT Signing](#jwt-signing)
- [Development Server](#development-server)
- [Tests and Code Quality](#tests-and-code-quality)
- [Documentation](#documentation)

## Requirements

- PHP 8.5
- Composer
- PostgreSQL with `ext-pdo_pgsql`
- Redis for built-in rate limiting, request replay protection and optional Redis HTTP response caching
- `ext-ds`, installed with PHP Installer for Extensions: `pie install php-ds/ext-ds`
- `ext-openssl` for RS256 JWT signing and verification

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

## JWT Signing

JWT tokens are signed with OpenSSL RSA keys using `RS256`. Generate a key pair outside the public web root:

```bash
mkdir -p var/keys
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out var/keys/jwt_private.pem
openssl rsa -in var/keys/jwt_private.pem -pubout -out var/keys/jwt_public.pem
chmod 600 var/keys/jwt_private.pem
chmod 644 var/keys/jwt_public.pem
```

Store absolute paths in `.env`:

```dotenv
JWT_PRIVATE_KEY_PATH=/absolute/path/to/var/keys/jwt_private.pem
JWT_PUBLIC_KEY_PATH=/absolute/path/to/var/keys/jwt_public.pem
JWT_PRIVATE_KEY_PASSPHRASE=
JWT_ACCESS_TOKEN_LIFETIME=7200
JWT_REFRESH_TOKEN_LIFETIME=1209600
REQUEST_SIGNATURE_SECRET=change-this-request-signature-secret-32-bytes-minimum
```

`JWT_PRIVATE_KEY_PASSPHRASE` may stay empty for an unencrypted private key. `REQUEST_SIGNATURE_SECRET` is separate from JWT signing and is used only by request signature HMAC validation.

The FrankenPHP dev app exposes `GET /dev/auth/token` to verify that a request is correctly authorized and signed. Import `dev/postman/addendum-dev-auth.postman_collection.json`, set `access_token`, `fingerprint` and `request_signature_secret`, then run the `Inspect Authorized Token` request. The signing script is also available as `dev/js/sign-request.js`.

Application token hashes are stored in the database and cached for 300 seconds under `auth:application_token:{jti}`. Validation compares the cached or database hash with `hash_equals()`. Revoke application tokens through `token_revocations` with an issue-time cutoff:

```bash
./bin/addendum app:revoke-tokens --type=application --uuid=external-api --before="2026-05-17 15:22:17" --reason=rotation
./bin/addendum app:revoke-tokens --type=application --uuid=external-api --before=1779031337 --reason=rotation
```

### User sessions and refresh tokens

Each login creates a session ID (`sid`) shared by its access and refresh tokens. Every token has its own `jti`. Refreshing issues a new pair with new token IDs while retaining the session ID.

| Endpoint | Accepted token | Behavior |
| --- | --- | --- |
| Protected API endpoints | User/admin access token, or application token where permitted | `Auth` always rejects refresh tokens |
| `POST /v1/session-refreshes` | `user_refresh` only | `RefreshAuth` validates the refresh token and session revocation; no access token is required |
| `DELETE /v1/sessions/current` | User/admin access token | Revokes all access and refresh tokens belonging to this session; other sessions remain active |

Refresh and logout requests retain request-signature, fingerprint and nonce/replay checks. To sign a refresh request with `dev/js/sign-request.js`, set its `access_token` variable to the refresh token for that request.

Apply pending migrations before running the updated application:

```bash
./bin/addendum db:migrate --run
./bin/addendum cache:warmup
```

Migration `009_session_revocations.sql` adds session revocations, retires obsolete SQL function overloads left by the original schema, and keeps the four-argument token validator for existing SQL callers. Authentication uses the new five-argument validator with `sid`. Previously issued user/admin/refresh tokens without `sid` are rejected and require a new login. Application tokens do not require `sid`.

Session revocation records are retained by `cleanup_expired_revocations()`: their creation date alone does not prove that all tokens in a refreshable session have expired.

The PostgreSQL-backed session lifecycle test can run against a dedicated database with all migrations applied. It rolls back its changes:

```bash
ADDENDUM_TEST_PG_DSN='pgsql:host=127.0.0.1;port=5432;dbname=addendum_test' \
ADDENDUM_TEST_PG_USER=addendum ADDENDUM_TEST_PG_PASSWORD=addendum \
./vendor/bin/phpunit dev/tests/phpunit/Auth/SessionRevocationIntegrationTest.php
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

## Tests and Code Quality

Install development dependencies with `composer install`, then run:

```bash
composer test
composer phpcs
composer phpstan
composer phpmd
composer audit --locked --format=plain
```

`composer quality` runs PHP_CodeSniffer, PHPStan and PHPMD sequentially and stops at the first failing check. PHPUnit and the dependency audit are separate commands.

The analyzers check all framework source code in `src/`:

| Tool | Configuration | Rules |
| --- | --- | --- |
| PHP_CodeSniffer 4 | `phpcs.xml.dist` | Full PSR-12; both errors and warnings fail the check |
| PHPStan 2 | `phpstan.neon.dist` | Level 6, targeting PHP 8.5 |
| PHPMD 3 | `phpmd.xml` | Full `codesize`, `design` and `unusedcode` rulesets |

Checks are strict from the first run: there are no baselines or excluded source files. PHP_CodeSniffer ignores suppression annotations and PHPMD runs with `--strict`. Existing violations also fail the checks.

GitHub Actions runs PHPUnit, coverage, dependency auditing and three independent quality jobs on pull requests and pushes to `main`. Workflows can also be started manually. Quality jobs report file annotations and continue independently when another analyzer fails. Composer Audit additionally runs every Monday to catch newly published advisories.

CodeQL uses Action v4 to analyze GitHub Actions workflow security. CodeQL does not support PHP; PHP source analysis is provided by PHPStan, PHP_CodeSniffer and PHPMD. CodeQL also runs every Monday.

## Documentation

- [HTTP Cache](docs/http-cache.md)
- [Compiled Application Cache](docs/compiled-cache.md)
- [Future Service Container Design](docs/container-design.md)
