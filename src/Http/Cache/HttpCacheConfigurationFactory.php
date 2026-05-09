<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use PCF\Addendum\Config\SystemEnvironmentProvider;
use RuntimeException;

final readonly class HttpCacheConfigurationFactory
{
    public function __construct(
        private SystemEnvironmentProvider $environmentProvider
    ) {
    }

    public function create(): HttpCacheConfigurationInterface
    {
        $provider = strtolower(trim($this->env('HTTP_CACHE_PROVIDER', 'none')));

        if ($provider === '' || $provider === 'none') {
            return new NoneHttpCache($this->context());
        }

        return match ($provider) {
            'redis' => $this->redis(),
            'varnish' => $this->varnish(),
            'nginx' => $this->nginx(),
            'caddy' => $this->caddy(),
            'cloudflare' => $this->cloudflare(),
            default => throw new RuntimeException(sprintf('Unsupported HTTP_CACHE_PROVIDER "%s"', $provider)),
        };
    }

    private function redis(): RedisHttpCache
    {
        return new RedisHttpCache(
            context: $this->context(),
            url: $this->nullableEnv('REDIS_HTTP_CACHE_URL'),
            host: $this->env('REDIS_HTTP_CACHE_HOST', '127.0.0.1'),
            port: $this->intEnv('REDIS_HTTP_CACHE_PORT', 6379),
            password: $this->nullableEnv('REDIS_HTTP_CACHE_PASSWORD'),
            database: $this->intEnv('REDIS_HTTP_CACHE_DATABASE', 1),
            keyPrefix: $this->env('REDIS_HTTP_CACHE_KEY_PREFIX', 'addendum:http_cache:'),
            hitHeader: $this->env('REDIS_HTTP_CACHE_HIT_HEADER', 'X-Redis-Cache')
        );
    }

    private function varnish(): VarnishHttpCache
    {
        return new VarnishHttpCache(
            context: $this->context(),
            tagHeader: $this->env('VARNISH_HTTP_CACHE_TAG_HEADER', 'Surrogate-Key'),
            surrogateControlHeader: $this->env('VARNISH_HTTP_CACHE_SURROGATE_CONTROL_HEADER', 'Surrogate-Control'),
            purgeUrl: $this->env('VARNISH_HTTP_CACHE_PURGE_URL', ''),
            purgeMethod: $this->env('VARNISH_HTTP_CACHE_PURGE_METHOD', 'PURGE')
        );
    }

    private function nginx(): NginxHttpCache
    {
        return new NginxHttpCache(
            context: $this->context(),
            tagHeader: $this->env('NGINX_HTTP_CACHE_TAG_HEADER', 'X-Cache-Tags'),
            accelExpiresHeader: $this->env('NGINX_HTTP_CACHE_ACCEL_EXPIRES_HEADER', 'X-Accel-Expires'),
            emitAccelExpires: $this->boolEnv('NGINX_HTTP_CACHE_EMIT_ACCEL_EXPIRES', true),
            purgeUrl: $this->env('NGINX_HTTP_CACHE_PURGE_URL', ''),
            purgeMethod: $this->env('NGINX_HTTP_CACHE_PURGE_METHOD', 'PURGE')
        );
    }

    private function caddy(): CaddyHttpCache
    {
        return new CaddyHttpCache(
            context: $this->context(),
            cacheHandler: $this->env('CADDY_HTTP_CACHE_HANDLER', CaddyHttpCache::STANDARD),
            tagHeader: $this->env('CADDY_HTTP_CACHE_TAG_HEADER', 'X-Cache-Tags'),
            souinTagHeader: $this->env('CADDY_HTTP_CACHE_SOUIN_TAG_HEADER', 'Souin-Cache-Tags')
        );
    }

    private function cloudflare(): CloudflareHttpCache
    {
        return new CloudflareHttpCache(
            context: $this->context(),
            zoneId: $this->env('CLOUDFLARE_HTTP_CACHE_ZONE_ID', ''),
            apiToken: $this->env('CLOUDFLARE_HTTP_CACHE_API_TOKEN', ''),
            tagHeader: $this->env('CLOUDFLARE_HTTP_CACHE_TAG_HEADER', 'Cache-Tag'),
            cdnCacheControlHeader: $this->env('CLOUDFLARE_HTTP_CACHE_CDN_CACHE_CONTROL_HEADER', 'CDN-Cache-Control'),
            cloudflareCacheControlHeader: $this->env('CLOUDFLARE_HTTP_CACHE_CONTROL_HEADER', 'Cloudflare-CDN-Cache-Control'),
            purgeByTags: $this->boolEnv('CLOUDFLARE_HTTP_CACHE_PURGE_BY_TAGS', false)
        );
    }

    private function context(): HttpCacheContext
    {
        $appEnv = strtolower($this->env('APP_ENV', 'prod'));

        return new HttpCacheContext(
            secretEnv: 'HTTP_CACHE_SECRET',
            authStateHeader: $this->env('HTTP_CACHE_AUTH_STATE_HEADER', 'X-Auth-State'),
            userContextHeader: $this->env('HTTP_CACHE_USER_CONTEXT_HEADER', 'X-User-Context-Hash'),
            userContextSignatureHeader: $this->env('HTTP_CACHE_USER_CONTEXT_SIGNATURE_HEADER', 'X-User-Context-Signature'),
            debugHeaders: $this->boolEnv('HTTP_CACHE_DEBUG_HEADERS', $appEnv === 'dev'),
            debugHeader: $this->env('HTTP_CACHE_DEBUG_HEADER', 'X-Http-Cache'),
            debugProviderHeader: $this->env('HTTP_CACHE_DEBUG_PROVIDER_HEADER', 'X-Http-Cache-Provider')
        );
    }

    private function env(string $name, string $default): string
    {
        return trim($this->environmentProvider->get($name, $default));
    }

    private function nullableEnv(string $name): ?string
    {
        $value = $this->env($name, '');

        return $value !== '' ? $value : null;
    }

    private function intEnv(string $name, int $default): int
    {
        $value = $this->env($name, (string) $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    private function boolEnv(string $name, bool $default): bool
    {
        $value = strtolower($this->env($name, $default ? 'true' : 'false'));

        return match ($value) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => $default,
        };
    }
}
