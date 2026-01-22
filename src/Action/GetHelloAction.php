<?php
declare(strict_types=1);

namespace PCF\Addendum\Action;

use PCF\Addendum\Action\ActionInterface;
use PCF\Addendum\Attribute\Middleware;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Http\Request;
use PCF\Addendum\Http\Middleware\Dummy;
use PCF\Addendum\Action\HelloResponse;
use PCF\Addendum\Action\HelloResponseFactory;

#[Route(path: '/hello', method: 'GET')]
#[Route(path: '/hello/:name', method: 'GET')]
#[Middleware(Dummy::class, ['header' => 'ok'])]
class GetHelloAction implements ActionInterface
{
    public function __invoke(Request $request): HelloResponse
    {
        $name = $request->get('name', 'world')
                |> (fn($str) => urldecode($str))
                |> (fn($str) => trim($str))
                |> (fn($str) => htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        return new HelloResponseFactory()->create($name);
    }
}
