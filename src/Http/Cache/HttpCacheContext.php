<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use Attribute;
use Psr\Http\Message\ResponseInterface;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class HttpCacheContext
{
    public function __construct(
        public string $secretEnv = 'HTTP_CACHE_SECRET',
        public string $authStateHeader = 'X-Auth-State',
        public string $userContextHeader = 'X-User-Context-Hash',
        public string $userContextSignatureHeader = 'X-User-Context-Signature',
        public bool $debugHeaders = false,
        public string $debugHeader = 'X-Http-Cache',
        public string $debugProviderHeader = 'X-Http-Cache-Provider'
    ) {
    }

    public function withDebugHeaders(ResponseInterface $response, string $state, string $provider): ResponseInterface
    {
        if (!$this->debugHeaders) {
            return $response;
        }

        return $response
            ->withHeader($this->debugHeader, $state)
            ->withHeader($this->debugProviderHeader, $provider);
    }

    public function getSecret(): ?string
    {
        $secret = $_ENV[$this->secretEnv] ?? getenv($this->secretEnv);

        if ($secret === false || $secret === null || $secret === '') {
            return null;
        }

        return (string) $secret;
    }

    public function signUserContext(string $userContextHash, string $userUuid, ?string $tokenType = null): ?string
    {
        $secret = $this->getSecret();

        if ($secret === null) {
            return null;
        }

        return hash_hmac('sha256', $this->userContextPayload($userContextHash, $userUuid, $tokenType), $secret);
    }

    public function isTrustedUserContext(
        string $userContextHash,
        string $signature,
        string $userUuid,
        ?string $tokenType = null
    ): bool {
        $expectedSignature = $this->signUserContext($userContextHash, $userUuid, $tokenType);

        return $expectedSignature !== null && hash_equals($expectedSignature, $signature);
    }

    private function userContextPayload(string $userContextHash, string $userUuid, ?string $tokenType): string
    {
        return $userUuid . '|' . ($tokenType ?? '') . '|' . $userContextHash;
    }
}
