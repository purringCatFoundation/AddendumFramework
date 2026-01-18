<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http;

use Psr\Http\Message\ServerRequestInterface;

class RequestFactory
{
    public function create(ServerRequestInterface $serverRequest): Request
    {
        return new Request($serverRequest);
    }
}
