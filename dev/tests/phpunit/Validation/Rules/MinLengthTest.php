<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Validation\Rules;

use PCF\Addendum\Validation\Rules\MinLength;
use PHPUnit\Framework\TestCase;

final class MinLengthTest extends TestCase
{
    public function testValidateWithValidLength(): void
    {
        $validator = new MinLength(5);

        $this->assertNull($validator->validate('hello'));
        $this->assertNull($validator->validate('hello world'));
        $this->assertNull($validator->validate('12345'));
    }

    public function testValidateWithTooShortValue(): void
    {
        $validator = new MinLength(5);

        $this->assertEquals('Field must be at least 5 characters long', $validator->validate('hi'));
        $this->assertEquals('Field must be at least 5 characters long', $validator->validate('a'));
        $this->assertEquals('Field must be at least 5 characters long', $validator->validate('1234'));
    }

    public function testValidateWithNullValue(): void
    {
        $validator = new MinLength(5);

        $this->assertNull($validator->validate(null));
    }

    public function testValidateWithExactLength(): void
    {
        $validator = new MinLength(5);

        $this->assertNull($validator->validate('abcde'));
    }

    public function testValidateWithDifferentMinLengths(): void
    {
        $validator1 = new MinLength(1);
        $validator10 = new MinLength(10);

        $this->assertNull($validator1->validate('a'));
        $this->assertEquals('Field must be at least 10 characters long', $validator10->validate('short'));
        $this->assertNull($validator10->validate('this is long enough'));
    }

    public function testValidateWithNumericValue(): void
    {
        $validator = new MinLength(3);

        $this->assertNull($validator->validate(123));
        $this->assertEquals('Field must be at least 3 characters long', $validator->validate(12));
    }

    public function testValidateWithZeroMinLength(): void
    {
        $validator = new MinLength(0);

        $this->assertNull($validator->validate(''));
        $this->assertNull($validator->validate('any'));
    }

    public function testValidateWithEmptyString(): void
    {
        $validator = new MinLength(1);

        $this->assertEquals('Field must be at least 1 characters long', $validator->validate(''));
    }
}
