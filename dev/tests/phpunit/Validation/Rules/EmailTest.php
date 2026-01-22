<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Validation\Rules;

use PCF\Addendum\Validation\Rules\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    private Email $validator;

    protected function setUp(): void
    {
        $this->validator = new Email();
    }

    public function testValidateWithValidEmail(): void
    {
        $this->assertNull($this->validator->validate('test@example.com'));
        $this->assertNull($this->validator->validate('user.name@domain.org'));
        $this->assertNull($this->validator->validate('user+tag@example.co.uk'));
        $this->assertNull($this->validator->validate('user123@subdomain.example.com'));
    }

    public function testValidateWithInvalidEmail(): void
    {
        $error = 'Field must be a valid email address';

        $this->assertEquals($error, $this->validator->validate('invalid'));
        $this->assertEquals($error, $this->validator->validate('invalid@'));
        $this->assertEquals($error, $this->validator->validate('@example.com'));
        $this->assertEquals($error, $this->validator->validate('invalid@.com'));
        $this->assertEquals($error, $this->validator->validate('invalid@com'));
    }

    public function testValidateWithNullValue(): void
    {
        $this->assertNull($this->validator->validate(null));
    }

    public function testValidateWithNonStringValue(): void
    {
        $error = 'Field must be a valid email address';

        $this->assertEquals($error, $this->validator->validate(123));
        $this->assertEquals($error, $this->validator->validate(['email@example.com']));
        $this->assertEquals($error, $this->validator->validate(true));
    }

    public function testValidateWithEmptyString(): void
    {
        $this->assertEquals('Field must be a valid email address', $this->validator->validate(''));
    }

    public function testValidateWithWhitespaceEmail(): void
    {
        $this->assertEquals('Field must be a valid email address', $this->validator->validate('  '));
        $this->assertEquals('Field must be a valid email address', $this->validator->validate(' test@example.com '));
    }
}
