<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Validation\Rules;

use PCF\Addendum\Validation\Rules\JwtToken;
use PHPUnit\Framework\TestCase;

final class JwtTokenTest extends TestCase
{
    public function testValidateWithMissingToken(): void
    {
        $validator = new JwtToken();

        $this->assertEquals('Token is required', $validator->validate(null));
        $this->assertEquals('Token is required', $validator->validate(''));
    }

    public function testValidateWithEmptyBearerToken(): void
    {
        $validator = new JwtToken();

        $this->assertEquals('Token is required', $validator->validate('   '));
    }

    public function testValidateWithMalformedJwtToken(): void
    {
        $previousJwtSecret = getenv('JWT_SECRET');
        $hadJwtSecret = array_key_exists('JWT_SECRET', $_ENV);
        $previousEnvJwtSecret = $_ENV['JWT_SECRET'] ?? null;

        putenv('JWT_SECRET=test-secret-for-malformed-token-validation');
        $_ENV['JWT_SECRET'] = 'test-secret-for-malformed-token-validation';

        $validator = new JwtToken();

        try {
            $result = $validator->validate('malformed.token');
            $this->assertStringStartsWith('Invalid token:', $result);
        } finally {
            if ($previousJwtSecret === false) {
                putenv('JWT_SECRET');
            } else {
                putenv('JWT_SECRET=' . $previousJwtSecret);
            }

            if ($hadJwtSecret) {
                $_ENV['JWT_SECRET'] = $previousEnvJwtSecret;
            } else {
                unset($_ENV['JWT_SECRET']);
            }
        }
    }

    public function testIsValidMethod(): void
    {
        $validator = new JwtToken();

        $this->assertFalse($validator->isValid(null));
        $this->assertFalse($validator->isValid('malformed.token'));
    }
}
