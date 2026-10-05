<?php

declare(strict_types=1);

namespace PCF\Addendum\Tests\Application;

use PCF\Addendum\Application\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Tester\CommandTester;

final class ApplicationTest extends TestCase
{
    public function testConsoleBootstrapPreservesMetadataAndLazyBuiltInCommands(): void
    {
        $console = FirstApplicationFixture::instance()->consoleApplication();
        $tester = new CommandTester($console->find('app:hello'));
        $tester->execute([]);

        self::assertSame('Application', $console->getName());
        self::assertSame('1.0.0', $console->getVersion());
        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('Hello, CitiesRPG!', $tester->getDisplay());
    }

    public function testSingletonIsScopedToEachApplicationClass(): void
    {
        $first = FirstApplicationFixture::instance();
        $second = SecondApplicationFixture::instance();

        self::assertInstanceOf(FirstApplicationFixture::class, $first);
        self::assertInstanceOf(SecondApplicationFixture::class, $second);
        self::assertSame($first, FirstApplicationFixture::instance());
        self::assertSame($second, SecondApplicationFixture::instance());
        self::assertNotSame($first, $second);
    }
}

final class FirstApplicationFixture extends Application
{
    public function consoleApplication(): ConsoleApplication
    {
        return $this->createConsoleApp();
    }

    public static function instance(): static
    {
        return static::getInstance();
    }
}

final class SecondApplicationFixture extends Application
{
    public static function instance(): static
    {
        return static::getInstance();
    }
}
