<?php
declare(strict_types=1);

namespace CitiesRpg\Tests\Action;

use CitiesRpg\ApiBackend\Action\GetHelloAction;
use PCF\Addendum\Action\HelloResponse;
use PHPUnit\Framework\TestCase;

final class HelloResponseTest extends TestCase
{
    public function testJsonSerialization(): void
    {
        class_exists(HelloAction::class);
        $response = new HelloResponse('Hello Bob');
        $this->assertJsonStringEqualsJsonString(
            '{"message":"Hello Bob"}',
            json_encode($response)
        );
    }
}
