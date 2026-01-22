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
        $validator = new JwtToken();

        $result = $validator->validate('malformed.token');
        $this->assertStringStartsWith('Configuration error:', $result);
    }

    public function testIsValidMethod(): void
    {
        $validator = new JwtToken();

        $this->assertFalse($validator->isValid(null));
        $this->assertFalse($validator->isValid('malformed.token'));
    }
}