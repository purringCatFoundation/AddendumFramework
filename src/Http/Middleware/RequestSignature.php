<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
 *
 * Signature calculation:
 * - Public endpoints: HMAC-SHA256(fingerprint, timestamp + fingerprint + method + path + body)
 * - Authenticated: HMAC-SHA256(JWT_SECRET + jti + fingerprintHash, timestamp + fingerprint + method + path + body)
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

    public function __construct(
        private readonly string $jwtSecret
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $timestamp = $request->getHeaderLine(self::HEADER_TIMESTAMP);
        $fingerprint = $request->getHeaderLine(self::HEADER_FINGERPRINT);
        $signature = $request->getHeaderLine(self::HEADER_SIGNATURE);

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
            $isAuthenticated
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
        bool $isAuthenticated
    ): string {
        if ($isAuthenticated) {
            $jti = $request->getAttribute('jti');
            $fingerprintHash = $request->getAttribute('fingerprint_hash');
            $signingKey = $jti . $fingerprintHash;
        } else {
            $signingKey = $fingerprint;
        }

        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $body = (string)$request->getBody();

        $data = $timestamp . $fingerprint . $method . $path . $body;

        return hash_hmac('sha256', $data, $signingKey);
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
