<?php

declare(strict_types=1);

namespace PCF\Addendum\Tests\Http;

use PCF\Addendum\Http\RouteParameters;
use PHPUnit\Framework\TestCase;

final class RouteParametersTest extends TestCase
{
    public function testPreservesStringKeysWithoutConvertingNumericNamesToIntegers(): void
    {
        $values = (static function (): iterable {
            yield 0 => 'full match';
            yield '123' => 'numeric-name';
            yield 'userId' => 42;
        })();

        $parameters = new RouteParameters($values);

        self::assertSame('numeric-name', $parameters->get('123'));
        self::assertSame('42', $parameters->get('userId'));
        self::assertNull($parameters->get('missing'));
        self::assertFalse($parameters->all()->hasKey(0));
        $copy = $parameters->all();
        $copy->put('userId', 'changed');
        self::assertSame('42', $parameters->get('userId'));
    }
}
