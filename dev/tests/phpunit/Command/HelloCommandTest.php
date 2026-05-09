<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Command;

use PCF\Addendum\Command\HelloCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class HelloCommandTest extends TestCase
{
    public function testOutputsGreeting(): void
    {
        $tester = new CommandTester(new HelloCommand());

        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Hello, CitiesRPG!', $tester->getDisplay());
    }
}
