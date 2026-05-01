<?php
declare(strict_types=1);

namespace PCF\Addendum\Dev\Action\HttpCache;

use PCF\Addendum\Action\ActionInterface;
use PCF\Addendum\Attribute\ResourcePolicy;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Http\Cache\HttpCacheMode;
use PCF\Addendum\Response\NoContentResponse;

#[Route(path: '/dev/http-cache/articles/:articleUuid', method: 'PATCH')]
#[ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 60, resource: 'article', idAttribute: 'articleUuid')]
final class PatchDevHttpCacheArticleAction implements ActionInterface
{
    public function __invoke(Request $request): NoContentResponse
    {
        return new NoContentResponse();
    }
}

final class PatchDevHttpCacheArticleActionFactory
{
    public function create(): PatchDevHttpCacheArticleAction
    {
        return new PatchDevHttpCacheArticleAction();
    }
}
