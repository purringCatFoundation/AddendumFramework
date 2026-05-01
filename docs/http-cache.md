# HTTP Cache

Addendum HTTP cache is configured by environment variables and described on endpoints with `#[ResourcePolicy]`. The backend is selected at runtime; endpoint code only declares whether a resource is public, guest-aware, user-aware or private.

## Enable A Backend

HTTP cache is disabled by default:

```env
HTTP_CACHE_PROVIDER=none
```

Missing `HTTP_CACHE_PROVIDER`, an empty value, or `none` disables the system. Disabled cache means no middleware is added, `#[ResourcePolicy]` is ignored and Redis is not initialized.

Supported providers:

| Value | Backend |
|-------|---------|
| `redis` | Stores serialized PSR-7 responses in Redis. |
| `varnish` | Emits Varnish-oriented HTTP cache headers. |
| `nginx` | Emits Nginx-oriented HTTP cache headers. |
| `caddy` | Emits Caddy or Souin cache tag headers. |
| `cloudflare` | Emits Cloudflare CDN cache headers. |

Shared context settings:

```env
HTTP_CACHE_SECRET=change-me
HTTP_CACHE_AUTH_STATE_HEADER=X-Auth-State
HTTP_CACHE_USER_CONTEXT_HEADER=X-User-Context-Hash
HTTP_CACHE_USER_CONTEXT_SIGNATURE_HEADER=X-User-Context-Signature
HTTP_CACHE_DEBUG_HEADERS=false
HTTP_CACHE_DEBUG_HEADER=X-Http-Cache
HTTP_CACHE_DEBUG_PROVIDER_HEADER=X-Http-Cache-Provider
```

`APP_ENV=dev` enables debug headers by default. Set `HTTP_CACHE_DEBUG_HEADERS=false` to disable them or `true` to force them in another environment. Debug responses include `X-Http-Cache: HIT|MISS|INVALIDATE` and `X-Http-Cache-Provider: redis|varnish|nginx|caddy|cloudflare`. For proxy providers, `MISS` means the origin generated the response; an edge hit normally never reaches PHP.

## Redis Backend

Redis is the only built-in backend that stores responses locally.

```env
HTTP_CACHE_PROVIDER=redis
REDIS_HTTP_CACHE_URL=
REDIS_HTTP_CACHE_HOST=127.0.0.1
REDIS_HTTP_CACHE_PORT=6379
REDIS_HTTP_CACHE_PASSWORD=
REDIS_HTTP_CACHE_DATABASE=1
REDIS_HTTP_CACHE_KEY_PREFIX=addendum:http_cache:
REDIS_HTTP_CACHE_HIT_HEADER=X-Redis-Cache
```

`REDIS_HTTP_CACHE_URL` has priority over host/port/password/database. When host/port configuration is used, `REDIS_HTTP_CACHE_DATABASE` selects the Redis DB for front page cache.

`REDIS_HTTP_CACHE_HIT_HEADER` is emitted with `HIT` or `MISS` only when debug headers are enabled.

`REDIS_HTTP_CACHE_KEY_PREFIX` is applied to every response and resource index key, for example:

```text
addendum:http_cache:response:<hash>
addendum:http_cache:resource:<hash>
```

## Varnish Backend

```env
HTTP_CACHE_PROVIDER=varnish
VARNISH_HTTP_CACHE_TAG_HEADER=Surrogate-Key
VARNISH_HTTP_CACHE_SURROGATE_CONTROL_HEADER=Surrogate-Control
VARNISH_HTTP_CACHE_PURGE_URL=
VARNISH_HTTP_CACHE_PURGE_METHOD=PURGE
```

Emitted headers include `Cache-Control`, `Surrogate-Control` and `Surrogate-Key`.

## Nginx Backend

```env
HTTP_CACHE_PROVIDER=nginx
NGINX_HTTP_CACHE_TAG_HEADER=X-Cache-Tags
NGINX_HTTP_CACHE_ACCEL_EXPIRES_HEADER=X-Accel-Expires
NGINX_HTTP_CACHE_EMIT_ACCEL_EXPIRES=true
NGINX_HTTP_CACHE_PURGE_URL=
NGINX_HTTP_CACHE_PURGE_METHOD=PURGE
```

Emitted headers include `Cache-Control`, `X-Accel-Expires` and `X-Cache-Tags`.

## Caddy Backend

```env
HTTP_CACHE_PROVIDER=caddy
CADDY_HTTP_CACHE_HANDLER=standard
CADDY_HTTP_CACHE_TAG_HEADER=X-Cache-Tags
CADDY_HTTP_CACHE_SOUIN_TAG_HEADER=Souin-Cache-Tags
```

Use `CADDY_HTTP_CACHE_HANDLER=souin` to emit `Souin-Cache-Tags` instead of the standard tag header.

## Cloudflare Backend

```env
HTTP_CACHE_PROVIDER=cloudflare
CLOUDFLARE_HTTP_CACHE_TAG_HEADER=Cache-Tag
CLOUDFLARE_HTTP_CACHE_CDN_CACHE_CONTROL_HEADER=CDN-Cache-Control
CLOUDFLARE_HTTP_CACHE_CONTROL_HEADER=Cloudflare-CDN-Cache-Control
CLOUDFLARE_HTTP_CACHE_ZONE_ID=
CLOUDFLARE_HTTP_CACHE_API_TOKEN=
CLOUDFLARE_HTTP_CACHE_PURGE_BY_TAGS=false
```

Emitted headers include `CDN-Cache-Control`, `Cloudflare-CDN-Cache-Control` and `Cache-Tag`.

## Resource Policy

Use repeatable `PCF\Addendum\Attribute\ResourcePolicy` on action classes:

```php
use PCF\Addendum\Attribute\ResourcePolicy;
use PCF\Addendum\Http\Cache\HttpCacheMode;

#[ResourcePolicy(
    mode: HttpCacheMode::PUBLIC,
    maxAge: 300,
    resource: 'article',
    idAttribute: 'articleUuid'
)]
final class GetArticleAction
{
}
```

Arguments:

| Argument | Type | Default | Meaning |
|----------|------|---------|---------|
| `mode` | `HttpCacheMode` | `PUBLIC` | Cache isolation mode. |
| `maxAge` | `int` | `0` | Cache TTL in seconds. `0` emits headers but does not store in Redis. |
| `resource` | `string` | `''` | Resource name used for tags and invalidation. |
| `idAttribute` | `?string` | `null` | Request attribute, route param, query param or body field containing resource ID. |
| `cacheErrors` | `bool` | `false` | Allows caching `4xx` and `5xx` responses when true. |

Resource names:

| Policy | Resolved Resource |
|--------|-------------------|
| `resource: 'articles'` | `articles` |
| `resource: 'article', idAttribute: 'articleUuid'` | `article:123` |

Repeat policies when one endpoint affects multiple resources:

```php
#[ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 300, resource: 'articles')]
#[ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 300, resource: 'article', idAttribute: 'articleUuid')]
final class PatchArticleAction
{
}
```

The first policy controls mode, TTL and error caching. All policies contribute resource names for tags and invalidation.

## Cache Modes

| Mode | Behavior |
|------|----------|
| `PUBLIC` | Same response for every caller. |
| `GUEST_AWARE` | Separate guest/authenticated variants using `X-Auth-State`. |
| `USER_AWARE` | Shared cache only with a trusted user context hash/signature. |
| `PRIVATE` | Emits `Cache-Control: private, no-store` and does not store. |

`GET`, `HEAD` and `OPTIONS` are cacheable. `POST`, `PUT`, `DELETE` and `PATCH` are invalidating methods and never store responses.

## Redis Read/Write Flow

For `GET`, `HEAD` and `OPTIONS` with `HTTP_CACHE_PROVIDER=redis`:

1. Middleware builds a cache key from method, path, query, mode and context.
2. Redis is checked before the action handler runs.
3. A hit returns the cached response with `X-Redis-Cache: HIT`.
4. A miss runs the action, applies headers, stores the response and returns `X-Redis-Cache: MISS`.

For successful mutations:

```http
Cache-Control: private, no-store
X-Cache-Invalidate: articles,article:123
```

Redis deletes every cached response indexed by those resources. Header-only providers add their backend tag header to the mutation response too: `Surrogate-Key`, `X-Cache-Tags`, `Souin-Cache-Tags` or `Cache-Tag`. Mutations with status `400` or higher do not invalidate.

## User-Aware Signature

`USER_AWARE` signatures bind the cache context to the authenticated user and token type.

Payload:

```text
userUuid + "|" + tokenType + "|" + userContextHash
```

Signature:

```text
HMAC-SHA256(HTTP_CACHE_SECRET, payload)
```

Required trusted request headers:

```http
X-User-Context-Hash: user:123:role:user
X-User-Context-Signature: <hmac-sha256>
```

The HTTP cache layer must strip client-provided context headers and inject trusted values. If the context is missing or invalid, `USER_AWARE` falls back to `private, no-store`.

## Local Dev Smoke Test

The FrankenPHP dev stack enables Redis HTTP cache and exposes dev-only endpoints through `dev/frankenphp/DevApp.php`:

```text
GET     /dev/http-cache/articles/:articleUuid
HEAD    /dev/http-cache/articles/:articleUuid
OPTIONS /dev/http-cache/articles/:articleUuid
PATCH   /dev/http-cache/articles/:articleUuid
```

Run:

```bash
docker compose up -d frankenphp
bash dev/test-http-cache.sh
```

The smoke test verifies `MISS`, `HIT`, invalidation, `HEAD` headers and `OPTIONS` headers.
