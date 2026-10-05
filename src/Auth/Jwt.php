<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

use DateTime;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Core\AlgorithmManager;
use InvalidArgumentException;
use JsonException;

class Jwt
{
    private const int CLOCK_SKEW_SECONDS = 60;
    private const string ALGORITHM = 'RS256';

    public static function encode(TokenPayload $payload, string $privateKeyPath, ?string $privateKeyPassphrase = null): string
    {
        $jwk = JWKFactory::createFromKeyFile($privateKeyPath, $privateKeyPassphrase);
        $algManager = new AlgorithmManager([new RS256()]);
        $builder = new JWSBuilder($algManager);
        $jws = $builder
            ->create()
            ->withPayload(json_encode($payload, JSON_THROW_ON_ERROR))
            ->addSignature($jwk, [
                'alg' => self::ALGORITHM,
                'appTokenType' => $payload->getTokenType(),
            ])
            ->build();
        $serializer = new CompactSerializer();
        return $serializer->serialize($jws, 0);
    }

    public static function decode(string $token, string $publicKeyPath, ?DateTime $now = null): TokenPayload
    {
        $serializer = new CompactSerializer();
        $jws = $serializer->unserialize($token);
        $algManager = new AlgorithmManager([new RS256()]);
        $verifier = new JWSVerifier($algManager);
        $jwk = JWKFactory::createFromKeyFile($publicKeyPath);
        if (!$verifier->verifyWithKey($jws, $jwk, 0)) {
            throw new InvalidArgumentException('Invalid signature');
        }

        $protectedHeader = $jws->getSignature(0)->getProtectedHeader();
        if (!isset($protectedHeader['appTokenType']) || !is_string($protectedHeader['appTokenType']) || trim($protectedHeader['appTokenType']) === '') {
            throw new InvalidArgumentException('Missing required JWT header: appTokenType');
        }

        try {
            $payload = json_decode($jws->getPayload() ?? '', true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Invalid payload', 0, $exception);
        }

        if (!is_array($payload)) {
            throw new InvalidArgumentException('Invalid payload');
        }

        if (array_key_exists('type', $payload)) {
            throw new InvalidArgumentException('Invalid JWT claim: type');
        }

        foreach (['sub', 'exp', 'jti', 'iat', 'tokenType'] as $requiredClaim) {
            if (!array_key_exists($requiredClaim, $payload)) {
                throw new InvalidArgumentException("Missing required JWT claim: {$requiredClaim}");
            }
        }

        foreach (['sub', 'jti', 'tokenType'] as $stringClaim) {
            if (!is_string($payload[$stringClaim]) || trim($payload[$stringClaim]) === '') {
                throw new InvalidArgumentException("Invalid JWT claim: {$stringClaim}");
            }
        }

        foreach (['exp', 'iat'] as $integerClaim) {
            if (!is_int($payload[$integerClaim]) && !(is_string($payload[$integerClaim]) && ctype_digit($payload[$integerClaim]))) {
                throw new InvalidArgumentException("Invalid JWT claim: {$integerClaim}");
            }
        }

        if ($protectedHeader['appTokenType'] !== $payload['tokenType']) {
            throw new InvalidArgumentException('JWT header appTokenType does not match tokenType claim');
        }

        self::assertSessionId($payload);

        $expiresAt = (int) $payload['exp'];
        $issuedAt = (int) $payload['iat'];
        $nowTimestamp = ($now ?? new DateTime())->getTimestamp();

        if ($nowTimestamp >= $expiresAt) {
            throw new InvalidArgumentException('Token expired');
        }

        if ($issuedAt > $nowTimestamp + self::CLOCK_SKEW_SECONDS) {
            throw new InvalidArgumentException('Token issued in the future');
        }

        if ($expiresAt <= $issuedAt) {
            throw new InvalidArgumentException('Token expiration must be after issue time');
        }

        return TokenPayload::fromArray($payload);
    }

    /** @param array<string, mixed> $payload */
    private static function assertSessionId(array $payload): void
    {
        $requiresSession = in_array(
            $payload['tokenType'],
            [TokenType::USER, TokenType::ADMIN, TokenType::USER_REFRESH],
            true
        );
        if (!$requiresSession && !array_key_exists('sid', $payload)) {
            return;
        }

        $sid = $payload['sid'] ?? null;
        if (!is_string($sid) || trim($sid) === '' || strlen($sid) > 255) {
            throw new InvalidArgumentException('Invalid JWT claim: sid');
        }
    }
}
