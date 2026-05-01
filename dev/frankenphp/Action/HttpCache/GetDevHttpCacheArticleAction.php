<?php
declare(strict_types=1);

namespace PCF\Addendum\Dev\Action\HttpCache;

use JsonSerializable;
use PCF\Addendum\Action\ActionInterface;
use PCF\Addendum\Attribute\ResourcePolicy;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Http\Cache\HttpCacheMode;

#[Route(path: '/dev/http-cache/articles/:articleUuid', method: 'GET')]
#[Route(path: '/dev/http-cache/articles/:articleUuid', method: 'HEAD')]
#[Route(path: '/dev/http-cache/articles/:articleUuid', method: 'OPTIONS')]
#[ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 60, resource: 'article', idAttribute: 'articleUuid')]
final class GetDevHttpCacheArticleAction implements ActionInterface
{
    public function __invoke(Request $request): JsonSerializable
    {
        return new DevHttpCacheArticleResponse(
            articleUuid: (string) $request->get('articleUuid'),
            generatedAt: microtime(true)
        );
    }
}

final class GetDevHttpCacheArticleActionFactory
{
    public function create(): GetDevHttpCacheArticleAction
    {
        return new GetDevHttpCacheArticleAction();
    }
}

final readonly class DevHttpCacheArticleResponse implements JsonSerializable
{
    public function __construct(
        private string $articleUuid,
        private float $generatedAt
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'articleUuid' => $this->articleUuid,
            'generatedAt' => $this->generatedAt,
        ];
    }
}
