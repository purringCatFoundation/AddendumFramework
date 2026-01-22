<?php

declare(strict_types=1);

namespace CitiesRpg\Tests;

use PCF\Addendum\Application\Main;
use PHPUnit\Framework\TestCase;

final class MainTest extends TestCase
{
    public function testCanInstantiateMain(): void
    {
        $this->assertInstanceOf(Main::class, new Main());
    }
}
