# PCF Addendum Framework

A PHP 8.5-only API framework with attribute-based routing, JWT authentication, request signature verification, PostgreSQL support, and CLI tooling.

## Features

- **Attribute-based routing** - Define routes directly on action classes using `#[Route]`
- **JWT Authentication** - Stateless authentication with token types (USER, CHARACTER, ADMIN, APPLICATION)
- **Request Signatures** - HMAC-SHA256 request signing with device binding for security
- **Middleware Pipeline** - PSR-15 compliant middleware system
- **Validation** - Declarative request validation with `#[ValidateRequest]` attributes
- **CLI Support** - Symfony Console integration with auto-discovered commands
- **Rate Limiting** - Built-in rate limiting middleware

## Requirements

- PHP 8.5
- `ext-ds` installed with PHP Installer for Extensions (PIE): `pie install php-ds/ext-ds`
- `ext-pdo_pgsql`
- PostgreSQL
- Redis
- Composer

## Development Server

The default Docker Compose stack runs the API server on FrankenPHP with Xdebug enabled. It also starts PostgreSQL and Redis, so a plain `docker compose up` from the repository root gives you a working local API environment.

The root `compose.yaml` includes `dev/docker-compose.yaml`; use the root-level commands below unless you intentionally want to address the dev compose file directly.

Default services:

- `frankenphp` - API server on `http://localhost:8080`
- `postgres` - PostgreSQL on `localhost:5432`
- `redis` - Redis on `localhost:6379`

Test services are not started by default. They are available through Compose profiles.

Build the FrankenPHP image:

```bash
docker compose build frankenphp
```

Start the server in the foreground:

```bash
docker compose up
```

Start the server in the background:

```bash
docker compose up -d
```

Open the server at `http://localhost:8080`.

Check that FrankenPHP and Xdebug are loaded:

```bash
curl -fsS http://localhost:8080/health.php
```

Follow server logs:

```bash
docker compose logs -f frankenphp
```

Stop the server and dependencies:

```bash
docker compose stop
```

Run PHPUnit explicitly when needed:

```bash
docker compose --profile test run --rm app
```

Run database pgTAP tests explicitly when needed:

```bash
docker compose --profile database run --rm database-tests
```

Public framework routes, including `/hello`, do not require request signing headers. Routes that declare `Auth` middleware, directly with `#[Middleware(Auth::class)]` or indirectly through `#[AccessControl(...)]`, require the normal token and request signature checks. `/health.php` checks the server without entering framework routing.

Xdebug defaults:

- Mode: `debug,develop`
- Start with request: `yes`
- Client host: `host.docker.internal`
- Client port: `9003`
- IDE key: `PHPSTORM`
- IDE server name: `addendum-frankenphp`
- Path mapping: project root to `/app`

Configure your IDE debug server with the name `addendum-frankenphp`, listening on port `9003`, and map this project directory to `/app`.

Override Xdebug settings through environment variables, for example:

```bash
XDEBUG_START_WITH_REQUEST=trigger docker compose up frankenphp
```

## Design Principles

- **Attribute-first APIs** - routes, validation, authorization, rate limits and middleware are declared on endpoint classes.
- **PSR-first contracts** - use PHP-FIG interfaces where they exist instead of framework-specific replacements.
- **PostgreSQL-only persistence** - runtime database access targets PostgreSQL only; every table and PostgreSQL function must have database tests.
- **Object-oriented data flow** - structured data moves through DTO/value objects; collections use collection objects such as `ArrayObject` or `ext-ds` collections. Plain arrays are limited to PHP/vendor boundaries like PDO parameters, PSR headers and final JSON serialization.
- **Security by default** - endpoints should opt into explicit authorization, rate limiting, request validation and security headers through attributes or global middleware.

## Quick Start

### 1. Create Your Application

Create an `App.php` file that extends the base `Application` class:

```php
<?php
declare(strict_types=1);

namespace YourApp;

use PCF\Addendum\Application\Application;
use PCF\Addendum\Attribute\Actions;
use PCF\Addendum\Attribute\Commands;
use PCF\Addendum\Attribute\Name;
use PCF\Addendum\Attribute\Version;

#[Name('MyApplication')]
#[Version('1.0.0')]
#[Actions(__DIR__ . '/Action')]
#[Commands(__DIR__ . '/Command')]
final class App extends Application
{
}
```

### 2. Create Entry Points

**HTTP Entry Point** (`pub/index.php`):

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use YourApp\App;

App::http();
```

**CLI Entry Point** (`bin/app`):

```php
#!/usr/bin/env php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use YourApp\App;

App::console();
```

### 3. Create an Action

Actions are single-purpose request handlers:

```php
<?php
declare(strict_types=1);

namespace YourApp\Action;

use PCF\Addendum\Action\ActionInterface;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Attribute\Middleware;
use PCF\Addendum\Attribute\ValidateRequest;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Validation\Rules\Required;
use PCF\Addendum\Validation\Rules\Email;

#[Route(path: '/users', method: 'POST')]
#[ValidateRequest('email', new Required())]
#[ValidateRequest('email', new Email())]
#[ValidateRequest('password', new Required())]
class CreateUserAction implements ActionInterface
{
    public function __invoke(Request $request): CreateUserResponse
    {
        $email = $request->get('email');
        $password = $request->get('password');

        // Your logic here...

        return new CreateUserResponse($user);
    }
}
```

### 4. Environment Configuration

Create a `.env` file in your project root:

```env
# Database (PostgreSQL only)
POSTGRES_HOST=localhost
POSTGRES_DB=myapp
POSTGRES_USER=myapp
POSTGRES_PASSWORD=secret

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=

# JWT
JWT_SECRET=your-secret-key-min-32-characters

# Application
APP_ENV=development
DEBUG=true
```

---

## Request Signatures

All API requests must include signature headers for security. This protects against:

- Request tampering
- Replay attacks
- Token theft
- Man-in-the-middle attacks

### Required Headers

Every request must include these headers:

| Header | Description |
|--------|-------------|
| `X-Request-Timestamp` | Unix timestamp (max 5 minutes old) |
| `X-Request-Fingerprint` | Unique device/client identifier |
| `X-Request-Signature` | HMAC-SHA256 signature |
| `X-Request-Nonce` | Unique per-request nonce when replay cache is configured |
| `Authorization` | Bearer token (authenticated endpoints only) |

Do not expose `JWT_SECRET` to browser or mobile clients. Authenticated request signing that depends on the server secret is intended for trusted server-side clients unless an application introduces a separate per-client signing-secret exchange.

### Signature Calculation

#### Public Endpoints (Login, Register)

For unauthenticated endpoints, the signature uses the fingerprint as the signing key:

```
data = timestamp + fingerprint + method + pathWithQuery + nonce + body
signature = HMAC-SHA256(fingerprint, data)
```

#### Authenticated Endpoints

For authenticated endpoints, the signature uses a composite key:

```
data = timestamp + fingerprint + method + pathWithQuery + nonce + body
signingKey = HMAC-SHA256(JWT_SECRET, jti + fingerprintHash)
signature = HMAC-SHA256(signingKey, data)
```

Where:
- `jti` is the JWT token ID (from the token payload)
- `fingerprintHash` is stored in the token and must match `SHA1(fingerprint)`

### Device Binding

JWT tokens are bound to specific devices:

1. During login/register, the server creates `fingerprintHash = SHA1(fingerprint)`
2. This hash is stored in the JWT token
3. On each request, the server verifies the fingerprint matches the token
4. Stolen tokens cannot be used from different devices

---

## Client Implementation Examples

### JavaScript/TypeScript

```typescript
import crypto from 'crypto';

// Generate and store device fingerprint (persistent)
function getDeviceFingerprint(): string {
  let fingerprint = localStorage.getItem('device_fingerprint');
  if (!fingerprint) {
    const components = [
      navigator.userAgent,
      navigator.language,
      screen.width + 'x' + screen.height,
      screen.colorDepth,
      new Date().getTimezoneOffset(),
    ];
    fingerprint = btoa(components.join('|'));
    localStorage.setItem('device_fingerprint', fingerprint);
  }
  return fingerprint;
}

// Generate request signature
function signRequest(params: {
  method: string;
  path: string;
  body: object | null;
  fingerprint: string;
  token?: string;      // JWT token (for authenticated requests)
  jwtSecret?: string;  // Trusted server-side clients only
}): { timestamp: number; nonce: string; signature: string } {
  const timestamp = Math.floor(Date.now() / 1000);
  const nonce = crypto.randomUUID();
  const bodyString = params.body ? JSON.stringify(params.body) : '';
  const data = `${timestamp}${params.fingerprint}${params.method}${params.path}${nonce}${bodyString}`;

  let signingKey: string;

  if (params.token && params.jwtSecret) {
    // Authenticated request
    const payload = JSON.parse(atob(params.token.split('.')[1]));
    const jti = payload.jti;
    const fingerprintHash = payload.fingerprintHash || '';
    signingKey = crypto
      .createHmac('sha256', params.jwtSecret)
      .update(`${jti}${fingerprintHash}`)
      .digest('hex');
  } else {
    // Public request
    signingKey = params.fingerprint;
  }

  const signature = crypto
    .createHmac('sha256', signingKey)
    .update(data)
    .digest('hex');

  return { timestamp, nonce, signature };
}
```

### Example: User Registration (Public Endpoint)

```typescript
async function register(email: string, password: string) {
  const method = 'POST';
  const path = '/v1/users';
  const body = { email, password };
  const fingerprint = getDeviceFingerprint();

  const { timestamp, nonce, signature } = signRequest({
    method,
    path,
    body,
    fingerprint,
  });

  const response = await fetch(`https://api.example.com${path}`, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-Request-Timestamp': timestamp.toString(),
      'X-Request-Fingerprint': fingerprint,
      'X-Request-Nonce': nonce,
      'X-Request-Signature': signature,
    },
    body: JSON.stringify(body),
  });

  return response.json();
}
```

### Example: Login

```typescript
async function login(email: string, password: string) {
  const method = 'POST';
  const path = '/v1/sessions';
  const body = { email, password };
  const fingerprint = getDeviceFingerprint();

  const { timestamp, nonce, signature } = signRequest({
    method,
    path,
    body,
    fingerprint,
  });

  const response = await fetch(`https://api.example.com${path}`, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-Request-Timestamp': timestamp.toString(),
      'X-Request-Fingerprint': fingerprint,
      'X-Request-Nonce': nonce,
      'X-Request-Signature': signature,
    },
    body: JSON.stringify(body),
  });

  // Response includes access_token and refresh_token
  return response.json();
}
```

### Example: Authenticated Request

```typescript
async function getCharacters(accessToken: string) {
  const method = 'GET';
  const path = '/v1/characters';
  const fingerprint = getDeviceFingerprint();

  const { timestamp, nonce, signature } = signRequest({
    method,
    path,
    body: null,
    fingerprint,
    token: accessToken,
    jwtSecret: process.env.JWT_SECRET, // Server-side only!
  });

  const response = await fetch(`https://api.example.com${path}`, {
    method,
    headers: {
      'Authorization': `Bearer ${accessToken}`,
      'X-Request-Timestamp': timestamp.toString(),
      'X-Request-Fingerprint': fingerprint,
      'X-Request-Nonce': nonce,
      'X-Request-Signature': signature,
    },
  });

  return response.json();
}
```

### Python Example

```python
import hashlib
import hmac
import json
import secrets
import time
import requests

def get_device_fingerprint() -> str:
    """Generate or retrieve device fingerprint."""
    # In a real app, persist this value
    return "unique-device-identifier"

def sign_request(
    method: str,
    path: str,
    body: dict | None,
    fingerprint: str,
    token: str | None = None,
    jwt_secret: str | None = None,
) -> tuple[int, str, str]:
    """Generate request signature."""
    timestamp = int(time.time())
    nonce = secrets.token_hex(16)
    body_string = json.dumps(body, separators=(',', ':')) if body else ''
    data = f"{timestamp}{fingerprint}{method}{path}{nonce}{body_string}"

    if token and jwt_secret:
        # Authenticated request
        import base64
        payload = json.loads(base64.b64decode(token.split('.')[1] + '=='))
        jti = payload.get('jti', '')
        fingerprint_hash = payload.get('fingerprintHash', '')
        signing_key = hmac.new(
            jwt_secret.encode(),
            f'{jti}{fingerprint_hash}'.encode(),
            hashlib.sha256
        ).hexdigest()
    else:
        # Public request
        signing_key = fingerprint

    signature = hmac.new(
        signing_key.encode(),
        data.encode(),
        hashlib.sha256
    ).hexdigest()

    return timestamp, nonce, signature

# Example: Login
def login(email: str, password: str):
    method = 'POST'
    path = '/v1/sessions'
    body = {'email': email, 'password': password}
    fingerprint = get_device_fingerprint()

    timestamp, nonce, signature = sign_request(method, path, body, fingerprint)

    response = requests.post(
        f'https://api.example.com{path}',
        json=body,
        headers={
            'X-Request-Timestamp': str(timestamp),
            'X-Request-Fingerprint': fingerprint,
            'X-Request-Nonce': nonce,
            'X-Request-Signature': signature,
        }
    )

    return response.json()
```

### cURL Examples

```bash
# Variables
FINGERPRINT="my-device-fingerprint"
TIMESTAMP=$(date +%s)
NONCE=$(openssl rand -hex 16)
METHOD="POST"
PATH="/v1/sessions"
BODY='{"email":"user@example.com","password":"secret123"}'

# Calculate signature for public endpoint
DATA="${TIMESTAMP}${FINGERPRINT}${METHOD}${PATH}${NONCE}${BODY}"
SIGNATURE=$(echo -n "$DATA" | openssl dgst -sha256 -hmac "$FINGERPRINT" | cut -d' ' -f2)

# Make request
curl -X POST "https://api.example.com${PATH}" \
  -H "Content-Type: application/json" \
  -H "X-Request-Timestamp: ${TIMESTAMP}" \
  -H "X-Request-Fingerprint: ${FINGERPRINT}" \
  -H "X-Request-Nonce: ${NONCE}" \
  -H "X-Request-Signature: ${SIGNATURE}" \
  -d "${BODY}"
```

---

## API Endpoints (Built-in)

The framework includes these built-in endpoints:

### Authentication

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/v1/users` | Register new user |
| `POST` | `/v1/sessions` | Login (get tokens) |
| `POST` | `/v1/sessions/refresh` | Refresh access token |
| `DELETE` | `/v1/sessions` | Logout |

### User Management

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/v1/users/me` | Get current user profile |
| `GET` | `/v1/users/:uuid` | Get user by UUID |
| `PATCH` | `/v1/users/me` | Update profile |
| `DELETE` | `/v1/users/me` | Delete account |

### Admin

| Method | Path | Description |
|--------|------|-------------|
| `DELETE` | `/v1/admin/tokens` | Revoke tokens (admin only) |

---

## CLI Commands (Built-in)

```bash
# Database migrations
php bin/app migrate

# List all routes
php bin/app app:routes
php bin/app app:routes --detailed
php bin/app app:routes --method=POST

# User management
php bin/app auth:logout <user-uuid>
php bin/app auth:logout --all

# Admin management
php bin/app app:grant-admin <email>
php bin/app app:revoke-admin <email>

# Application tokens
php bin/app app:generate-token <app-name> <owner-name> <owner-email>
php bin/app app:revoke-tokens --application=<name>

# Cron jobs
php bin/app cron:run

# Cache management
php bin/app cache:clear-proxy
```

---

## Token Types

The framework supports multiple token types for different contexts:

| Type | Purpose | Capabilities |
|------|---------|--------------|
| `USER` | After login | Manage characters, view profile |
| `CHARACTER` | After selecting character | In-game actions |
| `ADMIN` | Admin users | Full access, bypass ownership |
| `APPLICATION` | External services | Long-lived, elevated privileges |

### Token Flow

1. User logs in → receives `USER` token
2. User selects character → receives `CHARACTER` token
3. User performs in-game actions with `CHARACTER` token
4. User can switch back to `USER` context anytime

---

## Security Best Practices

1. **Always use HTTPS** - Never send signatures over unencrypted connections
2. **Persistent fingerprint** - Store device fingerprint consistently across sessions
3. **Clock synchronization** - Ensure client system time is accurate (NTP)
4. **Exact body match** - Use the exact same JSON string for signature and request
5. **Secure JWT_SECRET** - Keep secret secure, never expose in client-side code
6. **Fingerprint consistency** - Use the same fingerprint for all requests from a device

---

## License

MIT License
