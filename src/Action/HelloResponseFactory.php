<?php
declare(strict_types=1);

namespace PCF\Addendum\Action;

use PCF\Addendum\Action\HelloResponse;

class HelloResponseFactory
{
    public function create(string $name): HelloResponse
    {
        return new HelloResponse(sprintf('Hello %s', $name));
    }
}
