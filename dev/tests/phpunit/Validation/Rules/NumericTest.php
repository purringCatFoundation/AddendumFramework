<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Validation\Rules;

use PCF\Addendum\Validation\Rules\Numeric;
use PHPUnit\Framework\TestCase;

final class NumericTest extends TestCase
{
    public function testValidateWithValidNumericValue(): void
    {
        $validator = new Numeric();

        $this->assertNull($validator->validate('25'));
        $this->assertNull($validator->validate(25));
        $this->assertNull($validator->validate(25.5));
        $this->assertNull($validator->validate('25.5'));
    }

    public function testValidateWithInvalidValue(): void
    {
        $validator = new Numeric();

        $this->assertEquals('Field must be a number', $validator->validate('not-a-number'));
        $this->assertEquals('Field must be a number', $validator->validate('abc123'));
    }

    public function testValidateWithMissingValue(): void
    {
        $validator = new Numeric();

        $this->assertNull($validator->validate(null));
        $this->assertNull($validator->validate(''));
    }

    public function testValidateFromQuery(): void
    {
        $validator = new Numeric();

        $this->assertNull($validator->validate('5'));
    }

    public function testValidateFromHeader(): void
    {
        $validator = new Numeric();

        $this->assertNull($validator->validate('3'));
    }
}