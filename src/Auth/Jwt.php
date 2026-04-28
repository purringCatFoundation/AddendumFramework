<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Core\AlgorithmManager;
use InvalidArgumentException;
use JsonException;

class Jwt
{
    public static function encode(TokenPayload $payload, string $secret): string
    {
        $jwk = JWKFactory::createFromSecret($secret);
        $algManager = new AlgorithmManager([new HS256()]);
        $builder = new JWSBuilder($algManager);
        $jws = $builder
            ->create()
            ->withPayload(json_encode($payload, JSON_THROW_ON_ERROR))
            ->addSignature($jwk, ['alg' => 'HS256'])
            ->build();
        $serializer = new CompactSerializer();
        return $serializer->serialize($jws, 0);
    }

    public static function decode(string $token, string $secret): TokenPayload
    {
        $serializer = new CompactSerializer();
        $jws = $serializer->unserialize($token);
        $algManager = new AlgorithmManager([new HS256()]);
        $verifier = new JWSVerifier($algManager);
        $jwk = JWKFactory::createFromSecret($secret);
        if (!$verifier->verifyWithKey($jws, $jwk, 0)) {
            throw new InvalidArgumentException('Invalid signature');
        }
        try {
            $payload = json_decode($jws->getPayload() ?? '', true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Invalid payload', 0, $exception);
        }

        if (!is_array($payload)) {
            throw new InvalidArgumentException('Invalid payload');
        }

        foreach (['sub', 'exp', 'jti', 'iat', 'tokenType'] as $requiredClaim) {
            if (!array_key_exists($requiredClaim, $payload)) {
                throw new InvalidArgumentException("Missing required JWT claim: {$requiredClaim}");
            }
        }

        if (time() >= (int) $payload['exp']) {
            throw new InvalidArgumentException('Token expired');
        }

        return TokenPayload::fromArray($payload);
    }
}
