<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Request Signature Verification Middleware
 *
 * Verifies request integrity using HMAC-SHA256 signature for ALL endpoints.
 * Uses device fingerprint binding to prevent token theft.
 *
 * Required headers for ALL requests:
 * - X-Request-Timestamp: <unix_timestamp>
 * - X-Request-Fingerprint: <device_fingerprint>
 * - X-Request-Signature: <hmac_signature>
 * - X-Request-Nonce: <unique_request_nonce> when replay cache is configured
 *
 * Signature calculation:
 * - Public endpoints: HMAC-SHA256(fingerprint, timestamp + fingerprint + method + path + body)
 * - Authenticated: HMAC-SHA256(HMAC(JWT_SECRET, jti + fingerprintHash), timestamp + fingerprint + method + path + body)
 *
 * Protection against:
 * - Request tampering (body modification)
 * - Replay attacks (timestamp validation)
 * - Token theft (fingerprint binding)
 * - Man-in-the-middle attacks (signature verification)
 */
class RequestSignature implements MiddlewareInterface
{
    private const TIMESTAMP_TOLERANCE_SECONDS = 300; // 5 minutes
    private const HEADER_TIMESTAMP = 'X-Request-Timestamp';
    private const HEADER_FINGERPRINT = 'X-Request-Fingerprint';
    private const HEADER_SIGNATURE = 'X-Request-Signature';
    private const HEADER_NONCE = 'X-Request-Nonce';

    public function __construct(
        private readonly string $jwtSecret,
        private readonly ?CacheInterface $replayCache = null
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $timestamp = $request->getHeaderLine(self::HEADER_TIMESTAMP);
        $fingerprint = $request->getHeaderLine(self::HEADER_FINGERPRINT);
        $signature = $request->getHeaderLine(self::HEADER_SIGNATURE);
        $nonce = $request->getHeaderLine(self::HEADER_NONCE);

        if (empty($timestamp)) {
            return $this->createErrorResponse('Missing required header: ' . self::HEADER_TIMESTAMP, 400);
        }
        if (empty($fingerprint)) {
            return $this->createErrorResponse('Missing required header: ' . self::HEADER_FINGERPRINT, 400);
        }
        if (empty($signature)) {
            return $this->createErrorResponse('Missing required header: ' . self::HEADER_SIGNATURE, 400);
        }
        if (!ctype_digit($timestamp)) {
            return $this->createErrorResponse('Invalid timestamp format', 400);
        }
        if ($this->replayCache !== null && empty($nonce)) {
            return $this->createErrorResponse('Missing required header: ' . self::HEADER_NONCE, 400);
        }
        $timestampInt = (int)$timestamp;

        $now = time();
        $diff = abs($now - $timestampInt);

        if ($diff > self::TIMESTAMP_TOLERANCE_SECONDS) {
            return $this->createErrorResponse(
                'Request timestamp outside acceptable window (5 minutes)',
                400
            );
        }

        $isAuthenticated = $request->getAttribute('jti') !== null;
        $expectedSignature = $this->calculateSignature(
            $request,
            $timestampInt,
            $fingerprint,
            $isAuthenticated,
            $nonce
        );

        if (!hash_equals($expectedSignature, $signature)) {
            return $this->createErrorResponse('Invalid request signature', 403);
        }

        if ($isAuthenticated) {
            $tokenFingerprintHash = $request->getAttribute('fingerprint_hash');
            $requestFingerprintHash = sha1($fingerprint);

            if ($tokenFingerprintHash !== null && $tokenFingerprintHash !== $requestFingerprintHash) {
                return $this->createErrorResponse(
                    'Device fingerprint mismatch - token may have been stolen',
                    403
                );
            }
        }

        if ($this->replayCache !== null) {
            $replayKey = $this->createReplayCacheKey($request, $timestampInt, $fingerprint, $nonce, $signature);

            if ($this->replayCache->has($replayKey)) {
                return $this->createErrorResponse('Request nonce has already been used', 409);
            }

            $this->replayCache->set($replayKey, '1', self::TIMESTAMP_TOLERANCE_SECONDS);
        }

        // Signature valid, proceed with request
        return $handler->handle($request);
    }

    /**
     * Calculate HMAC-SHA256 signature for request
     *
     * @param ServerRequestInterface $request HTTP request
     * @param int $timestamp Request timestamp
     * @param string $fingerprint Device fingerprint
     * @param bool $isAuthenticated Whether request has valid token
     * @return string HMAC signature (hex)
     */
    private function calculateSignature(
        ServerRequestInterface $request,
        int $timestamp,
        string $fingerprint,
        bool $isAuthenticated,
        string $nonce
    ): string {
        if ($isAuthenticated) {
            $jti = $request->getAttribute('jti');
            $fingerprintHash = $request->getAttribute('fingerprint_hash');
            $signingKey = hash_hmac('sha256', (string) $jti . (string) $fingerprintHash, $this->jwtSecret);
        } else {
            $signingKey = $fingerprint;
        }

        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $query = $request->getUri()->getQuery();
        $body = (string)$request->getBody();

        $target = $query !== '' ? $path . '?' . $query : $path;
        $data = $timestamp . $fingerprint . $method . $target . $nonce . $body;

        return hash_hmac('sha256', $data, $signingKey);
    }

    private function createReplayCacheKey(
        ServerRequestInterface $request,
        int $timestamp,
        string $fingerprint,
        string $nonce,
        string $signature
    ): string {
        $jti = (string) $request->getAttribute('jti', 'public');
        $data = $timestamp . '|' . $fingerprint . '|' . $nonce . '|' . $signature . '|' . $jti;

        return 'request_signature_replay:' . hash('sha256', $data);
    }

    /**
     * Create error response
     */
    private function createErrorResponse(string $message, int $statusCode): ResponseInterface
    {
        $body = Utils::streamFor(json_encode([
            'error' => 'Signature Verification Failed',
            'message' => $message
        ]));

        return new PsrResponse(
            $statusCode,
            ['Content-Type' => 'application/json'],
            $body
        );
    }
}
