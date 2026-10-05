<?php

declare(strict_types=1);

namespace PCF\Addendum\Tests\Database;

use PCF\Addendum\Database\Testing\TapOutputParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TapOutputParserTest extends TestCase
{
    #[DataProvider('outputs')]
    public function testReportsTapFailuresAndProcessErrors(
        string $output,
        bool $processSuccess,
        bool $success,
        int $tests
    ): void {
        $result = new TapOutputParser()->parse($output, $processSuccess);

        self::assertSame($success, $result->success);
        self::assertSame($tests, $result->tests);
        self::assertSame($output, $result->output);
    }

    public static function outputs(): iterable
    {
        yield 'passing' => ["ok 1 - first\nok 2 - second", true, true, 2];
        yield 'not ok is failure' => ["ok 1 - first\nnot ok 2 - second", true, false, 2];
        yield 'status words in description' => ['ok 1 - rejects the string not ok 2', true, true, 1];
        yield 'psql indentation' => ["   not ok 1 - first\n   ok 2 - second", true, false, 2];
        yield 'table wrapping' => ['| ok 1 - first |', true, true, 1];
        yield 'sql error' => ['ERROR: query failed', true, false, 0];
        yield 'fatal error' => ['FATAL: connection failed', true, false, 0];
        yield 'process failed' => ['ok 1 - first', false, false, 1];
    }
}
