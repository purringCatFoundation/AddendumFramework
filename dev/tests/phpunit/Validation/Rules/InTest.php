<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Validation\Rules;

use PCF\Addendum\Validation\Rules\In;
use PHPUnit\Framework\TestCase;

final class InTest extends TestCase
{
    public function testValidateWithValidValue(): void
    {
        $validator = new In(['apple', 'banana', 'orange']);

        $this->assertNull($validator->validate('apple'));
        $this->assertNull($validator->validate('banana'));
        $this->assertNull($validator->validate('orange'));
    }

    public function testValidateWithInvalidValue(): void
    {
        $validator = new In(['apple', 'banana', 'orange']);

        $this->assertEquals('Field must be one of: apple, banana, orange', $validator->validate('grape'));
        $this->assertEquals('Field must be one of: apple, banana, orange', $validator->validate('mango'));
    }

    public function testValidateWithNullValue(): void
    {
        $validator = new In(['a', 'b', 'c']);

        $this->assertNull($validator->validate(null));
    }

    public function testValidateWithNumericValues(): void
    {
        $validator = new In([1, 2, 3]);

        $this->assertNull($validator->validate(1));
        $this->assertNull($validator->validate(2));
        $this->assertEquals('Field must be one of: 1, 2, 3', $validator->validate(4));
        $this->assertEquals('Field must be one of: 1, 2, 3', $validator->validate('1')); // Strict comparison
    }

    public function testValidateWithMixedValues(): void
    {
        $validator = new In(['yes', 'no', 1, 0]);

        $this->assertNull($validator->validate('yes'));
        $this->assertNull($validator->validate(1));
        $this->assertEquals('Field must be one of: yes, no, 1, 0', $validator->validate('maybe'));
    }

    public function testValidateWithSingleValue(): void
    {
        $validator = new In(['only']);

        $this->assertNull($validator->validate('only'));
        $this->assertEquals('Field must be one of: only', $validator->validate('other'));
    }

    public function testValidateWithEmptyArray(): void
    {
        $validator = new In([]);

        $this->assertEquals('Field must be one of: ', $validator->validate('anything'));
    }

    public function testValidateWithBooleanValues(): void
    {
        $validator = new In([true, false]);

        $this->assertNull($validator->validate(true));
        $this->assertNull($validator->validate(false));
        $this->assertEquals('Field must be one of: 1, ', $validator->validate(1)); // Strict comparison
    }

    public function testValidateWithStatusValues(): void
    {
        $validator = new In(['pending', 'active', 'inactive', 'deleted']);

        $this->assertNull($validator->validate('pending'));
        $this->assertNull($validator->validate('active'));
        $this->assertEquals('Field must be one of: pending, active, inactive, deleted', $validator->validate('unknown'));
    }

    public function testValidateWithCaseSensitivity(): void
    {
        $validator = new In(['Active', 'Inactive']);

        $this->assertNull($validator->validate('Active'));
        $this->assertEquals('Field must be one of: Active, Inactive', $validator->validate('active'));
        $this->assertEquals('Field must be one of: Active, Inactive', $validator->validate('ACTIVE'));
    }
}
