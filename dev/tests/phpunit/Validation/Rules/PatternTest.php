<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Validation\Rules;

use PCF\Addendum\Validation\Rules\Pattern;
use PHPUnit\Framework\TestCase;

final class PatternTest extends TestCase
{
    public function testValidateWithMatchingPattern(): void
    {
        $validator = new Pattern('/^[A-Z]{3}$/');

        $this->assertNull($validator->validate('ABC'));
        $this->assertNull($validator->validate('XYZ'));
    }

    public function testValidateWithNonMatchingPattern(): void
    {
        $validator = new Pattern('/^[A-Z]{3}$/');

        $this->assertEquals('Field does not match the required pattern', $validator->validate('abc'));
        $this->assertEquals('Field does not match the required pattern', $validator->validate('ABCD'));
        $this->assertEquals('Field does not match the required pattern', $validator->validate('AB'));
    }

    public function testValidateWithCustomErrorMessage(): void
    {
        $validator = new Pattern('/^[0-9]+$/', 'Only digits allowed');

        $this->assertEquals('Only digits allowed', $validator->validate('abc'));
        $this->assertNull($validator->validate('123'));
    }

    public function testValidateWithNullValue(): void
    {
        $validator = new Pattern('/^test$/');

        $this->assertNull($validator->validate(null));
    }

    public function testValidateWithPhonePattern(): void
    {
        $validator = new Pattern('/^\+?[0-9]{10,15}$/', 'Invalid phone number');

        $this->assertNull($validator->validate('+48123456789'));
        $this->assertNull($validator->validate('1234567890'));
        $this->assertEquals('Invalid phone number', $validator->validate('123'));
        $this->assertEquals('Invalid phone number', $validator->validate('phone'));
    }

    public function testValidateWithSlugPattern(): void
    {
        $validator = new Pattern('/^[a-z0-9-]+$/', 'Invalid slug format');

        $this->assertNull($validator->validate('my-slug-123'));
        $this->assertNull($validator->validate('valid'));
        $this->assertEquals('Invalid slug format', $validator->validate('Invalid Slug'));
        $this->assertEquals('Invalid slug format', $validator->validate('with_underscore'));
    }

    public function testValidateWithNumericValue(): void
    {
        $validator = new Pattern('/^[0-9]+$/');

        $this->assertNull($validator->validate(12345));
    }

    public function testValidateWithEmptyString(): void
    {
        $validator = new Pattern('/^.+$/');

        $this->assertEquals('Field does not match the required pattern', $validator->validate(''));
    }

    public function testValidateWithComplexPattern(): void
    {
        // Password pattern: at least 8 chars, 1 uppercase, 1 lowercase, 1 digit
        $validator = new Pattern(
            '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/',
            'Password must contain uppercase, lowercase and digit'
        );

        $this->assertNull($validator->validate('Password1'));
        $this->assertNull($validator->validate('StrongPass123'));
        $this->assertEquals('Password must contain uppercase, lowercase and digit', $validator->validate('weak'));
        $this->assertEquals('Password must contain uppercase, lowercase and digit', $validator->validate('nodigits'));
    }
}
