<?php
declare(strict_types=1);

namespace CitiesRpg\Tests\Db;

use PCF\Addendum\Database\MigrationRunner;
use PCF\Addendum\Database\DbConnectionFactory;
use PCF\Addendum\Util\FinderFactory;
use PDO;
use PHPUnit\Framework\TestCase;

final class MigrationRunnerTest extends TestCase
{
    public function testPendingAndRun(): void
    {
        $dir = sys_get_temp_dir() . '/migrations_' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/001_test.sql', 'CREATE TABLE test(id INTEGER);');

        $pdo = new PDO('sqlite::memory:');

        // Create a mock DbConnectionFactory that returns our test PDO
        $dbConnectionFactory = $this->createMock(DbConnectionFactory::class);
        $dbConnectionFactory->method('create')->willReturn($pdo);

        $runner = new MigrationRunner($dbConnectionFactory, $dir, new FinderFactory());

        $pending = $runner->pending();
        $this->assertTrue($pending->hasKey('001_test.sql'));

        $executed = $runner->run();
        $this->assertSame(['001_test.sql'], $executed->toArray());
        $this->assertTrue($runner->pending()->isEmpty());
    }
}
