<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action;

use Pradzikowski\Framework\Action\HelloResponse;

class HelloResponseFactory
{
    public function create(string $name): HelloResponse
    {
        return new HelloResponse(sprintf('Hello %s', $name));
    }
}
