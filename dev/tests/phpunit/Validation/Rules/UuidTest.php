<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Validation\Rules;

use PCF\Addendum\Validation\Rules\Uuid;
use PHPUnit\Framework\TestCase;

final class UuidTest extends TestCase
{
    private Uuid $validator;

    protected function setUp(): void
    {
        $this->validator = new Uuid();
    }

    public function testValidateWithValidUuidV4(): void
    {
        $this->assertNull($this->validator->validate('550e8400-e29b-41d4-a716-446655440000'));
        $this->assertNull($this->validator->validate('6ba7b810-9dad-41d4-80b4-00c04fd430c8'));
        $this->assertNull($this->validator->validate('f47ac10b-58cc-4372-a567-0e02b2c3d479'));
    }

    public function testValidateWithValidUuidV4Uppercase(): void
    {
        $this->assertNull($this->validator->validate('550E8400-E29B-41D4-A716-446655440000'));
        $this->assertNull($this->validator->validate('F47AC10B-58CC-4372-A567-0E02B2C3D479'));
    }

    public function testValidateWithInvalidUuid(): void
    {
        $this->assertEquals('Invalid UUID format (expected UUID v4)', $this->validator->validate('invalid-uuid'));
        $this->assertEquals('Invalid UUID format (expected UUID v4)', $this->validator->validate('not-a-uuid'));
        $this->assertEquals('Invalid UUID format (expected UUID v4)', $this->validator->validate('12345'));
    }

    public function testValidateWithNullValue(): void
    {
        $this->assertNull($this->validator->validate(null));
    }

    public function testValidateWithNonStringValue(): void
    {
        $this->assertEquals('UUID must be a string', $this->validator->validate(123));
        $this->assertEquals('UUID must be a string', $this->validator->validate(['uuid']));
        $this->assertEquals('UUID must be a string', $this->validator->validate(true));
    }

    public function testValidateWithWrongVersion(): void
    {
        // UUID v1 (timestamp-based) - version digit should be 1, not 4
        $this->assertEquals('Invalid UUID format (expected UUID v4)', $this->validator->validate('550e8400-e29b-11d4-a716-446655440000'));
    }

    public function testValidateWithWrongVariant(): void
    {
        // Wrong variant (should be 8, 9, a, or b)
        $this->assertEquals('Invalid UUID format (expected UUID v4)', $this->validator->validate('550e8400-e29b-41d4-0716-446655440000'));
        $this->assertEquals('Invalid UUID format (expected UUID v4)', $this->validator->validate('550e8400-e29b-41d4-c716-446655440000'));
    }

    public function testValidateWithEmptyString(): void
    {
        $this->assertEquals('Invalid UUID format (expected UUID v4)', $this->validator->validate(''));
    }

    public function testValidateWithMissingHyphens(): void
    {
        $this->assertEquals('Invalid UUID format (expected UUID v4)', $this->validator->validate('550e8400e29b41d4a716446655440000'));
    }

    public function testValidateWithExtraHyphens(): void
    {
        $this->assertEquals('Invalid UUID format (expected UUID v4)', $this->validator->validate('550e-8400-e29b-41d4-a716-446655440000'));
    }

    public function testValidateWithWrongLength(): void
    {
        $this->assertEquals('Invalid UUID format (expected UUID v4)', $this->validator->validate('550e8400-e29b-41d4-a716-44665544000'));
        $this->assertEquals('Invalid UUID format (expected UUID v4)', $this->validator->validate('550e8400-e29b-41d4-a716-4466554400000'));
    }
}
