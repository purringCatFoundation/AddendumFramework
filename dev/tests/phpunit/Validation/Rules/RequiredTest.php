<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Validation\Rules;

use PCF\Addendum\Validation\Rules\Required;
use PHPUnit\Framework\TestCase;

final class RequiredTest extends TestCase
{
    private Required $validator;

    protected function setUp(): void
    {
        $this->validator = new Required();
    }

    public function testValidateWithValidValue(): void
    {
        $this->assertNull($this->validator->validate('some value'));
        $this->assertNull($this->validator->validate(0));
        $this->assertNull($this->validator->validate(false));
        $this->assertNull($this->validator->validate(''));
        $this->assertNull($this->validator->validate([]));
    }

    public function testValidateWithNullReturnsError(): void
    {
        $this->assertEquals('Field is required', $this->validator->validate(null));
    }

    public function testValidateWithStringValue(): void
    {
        $this->assertNull($this->validator->validate('test'));
        $this->assertNull($this->validator->validate('a'));
    }

    public function testValidateWithNumericValue(): void
    {
        $this->assertNull($this->validator->validate(123));
        $this->assertNull($this->validator->validate(0));
        $this->assertNull($this->validator->validate(-1));
        $this->assertNull($this->validator->validate(1.5));
    }

    public function testValidateWithBooleanValue(): void
    {
        $this->assertNull($this->validator->validate(true));
        $this->assertNull($this->validator->validate(false));
    }

    public function testValidateWithArrayValue(): void
    {
        $this->assertNull($this->validator->validate([]));
        $this->assertNull($this->validator->validate([1, 2, 3]));
        $this->assertNull($this->validator->validate(['key' => 'value']));
    }

    public function testValidateWithObjectValue(): void
    {
        $this->assertNull($this->validator->validate(new \stdClass()));
    }
}
