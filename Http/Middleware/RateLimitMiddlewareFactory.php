<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Middleware;

use Pradzikowski\Framework\Http\Middleware\FactoryInterface;
use Predis\Client as RedisClient;

class RateLimitMiddlewareFactory implements FactoryInterface
{
    public function __construct(
        private RedisClient $redis
    ) {}

    public function create(): RateLimitMiddleware
    {
        return new RateLimitMiddleware($this->redis);
    }
}
