<?php

declare(strict_types=1);

namespace PCF\Addendum\Tests\Database;

use PCF\Addendum\Database\Testing\SqlStatementSplitter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SqlStatementSplitterTest extends TestCase
{
    #[DataProvider('statements')]
    public function testPreservesQuotedSemicolonsAndEscapedQuotes(string $sql, array $expected): void
    {
        self::assertSame($expected, new SqlStatementSplitter()->split($sql)->toArray());
    }

    public static function statements(): iterable
    {
        yield 'simple' => ['SELECT 1;SELECT 2;', ['SELECT 1', 'SELECT 2']];
        yield 'quoted semicolon' => ["SELECT 'a;b';SELECT 2", ["SELECT 'a;b'", 'SELECT 2']];
        yield 'escaped quote' => ["SELECT 'it''s;a';", ["SELECT 'it''s;a'"]];
        yield 'quoted identifier' => ['SELECT "a;b";', ['SELECT "a;b"']];
        yield 'unterminated string' => ["SELECT 'a;b", ["SELECT 'a;b"]];
        yield 'whitespace tail' => ['SELECT 1;   ', ['SELECT 1']];
    }
}
