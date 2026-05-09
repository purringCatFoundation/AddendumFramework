<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Command;

use PCF\Addendum\Command\CommandScanner;
use PCF\Addendum\Command\HelloCommand;
use PCF\Addendum\Command\HelloCommandFactory;
use PHPUnit\Framework\TestCase;

final class CommandScannerTest extends TestCase
{
    public function testMissingDirectoryReturnsNoCommands(): void
    {
        $scanner = new CommandScanner(sys_get_temp_dir() . '/missing-addendum-commands-' . uniqid('', true));

        self::assertTrue($scanner->scanCommands()->isEmpty());
    }

    public function testScansCommandDefinitionsFromDirectory(): void
    {
        $commandDirectory = realpath(__DIR__ . '/../../../../src/Command');
        self::assertIsString($commandDirectory);

        $commands = new CommandScanner($commandDirectory)->scanCommands();

        self::assertTrue($commands->hasKey('app:hello'));

        $definition = $commands->get('app:hello');
        self::assertSame('app:hello', $definition->name);
        self::assertSame('Outputs a greeting', $definition->description);
        self::assertSame(HelloCommand::class, $definition->class);
        self::assertSame(HelloCommandFactory::class, $definition->factory);
    }
}
