<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Command;

use PCF\Addendum\Command\ClearHttpCacheCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ClearHttpCacheCommandTest extends TestCase
{
    public function testSendsClearRequestAndReturnsSuccess(): void
    {
        $tester = new CommandTester(new ClearHttpCacheCommand('data://text/plain,ok'));

        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('HTTP cache clear request sent.', $tester->getDisplay());
    }
}
