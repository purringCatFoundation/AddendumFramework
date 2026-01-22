<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Validation\Rules;

use PCF\Addendum\Validation\Rules\MaxLength;
use PHPUnit\Framework\TestCase;

final class MaxLengthTest extends TestCase
{
    public function testValidateWithValidLength(): void
    {
        $validator = new MaxLength(10);

        $this->assertNull($validator->validate('hello'));
        $this->assertNull($validator->validate('short'));
        $this->assertNull($validator->validate('1234567890'));
    }

    public function testValidateWithTooLongValue(): void
    {
        $validator = new MaxLength(5);

        $this->assertEquals('Field must not exceed 5 characters', $validator->validate('toolong'));
        $this->assertEquals('Field must not exceed 5 characters', $validator->validate('hello world'));
        $this->assertEquals('Field must not exceed 5 characters', $validator->validate('123456'));
    }

    public function testValidateWithNullValue(): void
    {
        $validator = new MaxLength(5);

        $this->assertNull($validator->validate(null));
    }

    public function testValidateWithExactLength(): void
    {
        $validator = new MaxLength(5);

        $this->assertNull($validator->validate('abcde'));
    }

    public function testValidateWithDifferentMaxLengths(): void
    {
        $validator1 = new MaxLength(1);
        $validator100 = new MaxLength(100);

        $this->assertNull($validator1->validate('a'));
        $this->assertEquals('Field must not exceed 1 characters', $validator1->validate('ab'));
        $this->assertNull($validator100->validate('this is a long string that should pass validation'));
    }

    public function testValidateWithNumericValue(): void
    {
        $validator = new MaxLength(3);

        $this->assertNull($validator->validate(123));
        $this->assertEquals('Field must not exceed 3 characters', $validator->validate(1234));
    }

    public function testValidateWithEmptyString(): void
    {
        $validator = new MaxLength(5);

        $this->assertNull($validator->validate(''));
    }

    public function testValidateWithZeroMaxLength(): void
    {
        $validator = new MaxLength(0);

        $this->assertNull($validator->validate(''));
        $this->assertEquals('Field must not exceed 0 characters', $validator->validate('a'));
    }
}
