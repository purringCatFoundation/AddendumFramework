<?php

declare(strict_types=1);

namespace PCF\Addendum\Tests\Util;

use PCF\Addendum\Util\ClassNameReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ClassNameReaderTest extends TestCase
{
    #[DataProvider('sources')]
    public function testReadsDeclaredClassWithoutExecutingSource(string $source, ?string $expected): void
    {
        self::assertSame($expected, ClassNameReader::fromSource($source));
    }

    public static function sources(): iterable
    {
        yield 'qualified namespace' => ['<?php namespace Example\Http; final class Action {}', 'Example\Http\Action'];
        yield 'global namespace' => ['<?php class Action {}', 'Action'];
        yield 'braced namespace' => ['<?php namespace Example { class Action {} }', 'Example\Action'];
        yield 'comments' => ['<?php namespace Example; class /* comment */ Action {}', 'Example\Action'];
        yield 'no class' => ['<?php namespace Example; interface Action {}', null];
        yield 'anonymous class' => ['<?php $object = new class {}; class Action {}', 'Action'];
        yield 'class constant' => ['<?php $name = Other::class; class Action {}', 'Action'];
    }
}
